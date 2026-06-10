<?php

namespace OpenProvider\WhmcsDomainAddon\Controllers\System;

use WeDevelopCoffee\wPower\Core\Core;
use OpenProvider\WhmcsDomainAddon\Services\BulkTransfer\BulkTransferProcessor;
use OpenProvider\WhmcsDomainAddon\Services\BulkTransfer\BulkTransferTestHarness;

/**
 * Cron controller for bulk domain transfers.
 *
 * CLI arguments
 * ─────────────
 *   --submit-limit=N     Items per cron run for the submit lane (default 10).
 *   --status-limit=N     Items per cron run for the status lane (default 50).
 *   --limit=N            Alias for --submit-limit (legacy).
 *   --lane=submit|status|all  Run only one lane (default all).
 *   --test-mode          Activate the mock harness (see BulkTransferTestHarness).
 *   --debug              Enable verbose per-step timing output.
 *
 * Performance tuning notes
 * ─────────────────────────
 * Submit lane (processQueuedItems):
 *   Each item makes 3 external API calls: RC unlock, RC EPP, OP transfer.
 *   Typical latency per item: 2–6 s depending on registrar.
 *   At DEFAULT_SUBMIT_LIMIT=10:  ~20–60 s per cron run.
 *   Risk: if cron runs every 60 s and each run takes >60 s, runs overlap.
 *   → Recommendation: run submit lane on its own schedule (e.g. every 2 min)
 *     and size the limit so wall time stays under the interval.
 *   → With handle creation (~500 ms each) added: budget ~8 s/item worst case.
 *     Limit of 5 is safer for a 60 s interval; 10 for a 120 s interval.
 *
 * Status lane (processPendingTransferItems):
 *   Each item makes 1 OP getDomainDetails call (~300–800 ms).
 *   At DEFAULT_STATUS_LIMIT=50: ~15–40 s per cron run. Fine for 60 s interval.
 *   → 60-minute recheck window means a domain that completes quickly sits idle
 *     for up to an hour. Consider reducing to 15–30 min for faster finalization.
 *
 * refreshBatchStatistics is called on every item status transition (N×M writes).
 * For a 100-domain batch this adds ~100 aggregate queries per cron run.
 * → For large batches consider batching the stats update at lane end only.
 *
 * Concurrency:
 *   No advisory lock prevents two cron processes running simultaneously.
 *   The atomic claim mechanism prevents double-processing of individual items,
 *   but two parallel runs each consume their full limit — doubling API usage.
 *   → Add a file-based or DB advisory lock if cron scheduler can overlap.
 */
class BulkTransferCronController extends BaseController
{
    private const DEFAULT_SUBMIT_LIMIT = 10;
    private const DEFAULT_STATUS_LIMIT = 50;

    /** @var BulkTransferProcessor */
    protected $bulkTransferProcessor;

    public function __construct(
        Core $core,
        BulkTransferProcessor $bulkTransferProcessor
    ) {
        parent::__construct($core);
        $this->bulkTransferProcessor = $bulkTransferProcessor;
    }

    public function process($params)
    {
        $lane        = $this->normalizeLane($params['lane'] ?? 'all');
        $submitLimit = max(1, (int) (
            $params['submit_limit'] ?? $params['limit'] ?? self::DEFAULT_SUBMIT_LIMIT
        ));
        $statusLimit = max(1, (int) ($params['status_limit'] ?? self::DEFAULT_STATUS_LIMIT));
        $testMode    = !empty($params['test_mode']);

        // ── Test harness ───────────────────────────────────────────────────
        if ($testMode) {
            $harness = BulkTransferTestHarness::getInstance();
            $this->bulkTransferProcessor->setTestHarness($harness);
            $this->printDebug('[TEST MODE] Harness active. Proxy domain: ' . BulkTransferTestHarness::REAL_DOMAIN);
        }

        $this->start();
        $cronStart = microtime(true);

        // ── Base result structure ──────────────────────────────────────────
        $result = [
            'lane'         => $lane,
            'test_mode'    => $testMode,
            'status_checks' => ['claimed' => 0, 'processed' => 0, 'lane_ms' => 0, 'item_ms' => []],
            'submissions'   => ['claimed' => 0, 'processed' => 0, 'lane_ms' => 0, 'item_ms' => []],
        ];

        // ── Status lane ───────────────────────────────────────────────────
        if (in_array($lane, ['all', 'status'], true)) {
            $tStatus = microtime(true);
            $result['status_checks'] = $this->bulkTransferProcessor->processPendingTransferItems($statusLimit);
            $statusWall = round((microtime(true) - $tStatus) * 1000);

            $this->printDebug(sprintf(
                '[STATUS LANE]  claimed=%d  processed=%d  wall=%d ms  avg_item=%d ms',
                $result['status_checks']['claimed'],
                $result['status_checks']['processed'],
                $statusWall,
                $result['status_checks']['item_ms']['avg_ms'] ?? 0
            ));

            $this->warnIfSlowLane('status', $statusLimit, $result['status_checks']);
        }

        // ── Submit lane ───────────────────────────────────────────────────
        if (in_array($lane, ['all', 'submit'], true)) {
            $tSubmit = microtime(true);
            $result['submissions'] = $this->bulkTransferProcessor->processQueuedItems($submitLimit);
            $submitWall = round((microtime(true) - $tSubmit) * 1000);

            $this->printDebug(sprintf(
                '[SUBMIT LANE]  claimed=%d  processed=%d  wall=%d ms  avg_item=%d ms',
                $result['submissions']['claimed'],
                $result['submissions']['processed'],
                $submitWall,
                $result['submissions']['item_ms']['avg_ms'] ?? 0
            ));

            $this->warnIfSlowLane('submit', $submitLimit, $result['submissions']);
        }

        // ── Full-run summary ──────────────────────────────────────────────
        $totalMs = round((microtime(true) - $cronStart) * 1000);
        $result['cron_ms'] = $totalMs;

        if ($testMode) {
            $result['test_errors_injected'] = BulkTransferTestHarness::getInstance()->getErrorCount();
        }

        $this->logPerformanceSummary($result, $submitLimit, $statusLimit);

        $this->printDebug(sprintf('[CRON TOTAL] wall=%d ms', $totalMs));

        $this->shutdown('Bulk transfer cron processed');

        return $result;
    }

    // =========================================================================
    // Helpers
    // =========================================================================

    protected function normalizeLane($lane)
    {
        $lane = strtolower(trim((string) $lane));

        if ($lane === 'pending') {
            return 'status';
        }

        if (!in_array($lane, ['all', 'status', 'submit'], true)) {
            return 'all';
        }

        return $lane;
    }

    /**
     * Emits a debug warning when the average item time is high relative to
     * the configured limit — surfacing sizing issues early in testing.
     */
    protected function warnIfSlowLane(string $lane, int $limit, array $laneResult): void
    {
        $avgMs = $laneResult['item_ms']['avg_ms'] ?? 0;
        $maxMs = $laneResult['item_ms']['max_ms'] ?? 0;

        if ($avgMs === 0) {
            return;
        }

        // Warn if processing the full limit would take >90 s (typical cron interval)
        $projectedMs = $avgMs * $limit;

        if ($projectedMs > 90000) {
            $this->printDebug(sprintf(
                '[WARN] %s lane: avg item=%d ms, limit=%d → projected full-limit wall=%d s. '
                . 'Consider reducing --' . $lane . '-limit or increasing cron interval.',
                strtoupper($lane),
                $avgMs,
                $limit,
                (int) round($projectedMs / 1000)
            ));
        }

        if ($maxMs > 30000) {
            $this->printDebug(sprintf(
                '[WARN] %s lane: slowest item was %d ms. '
                . 'Investigate RC / OP API latency or add per-item timeout.',
                strtoupper($lane),
                $maxMs
            ));
        }
    }

    /**
     * Writes the performance summary to the WHMCS module log for post-run analysis.
     */
    protected function logPerformanceSummary(array $result, int $submitLimit, int $statusLimit): void
    {
        if (!\function_exists('logModuleCall')) {
            return;
        }

        \logModuleCall(
            'openprovider',
            'bulk_transfer_cron_perf_summary',
            [
                'lane'          => $result['lane'],
                'submit_limit'  => $submitLimit,
                'status_limit'  => $statusLimit,
                'test_mode'     => $result['test_mode'],
            ],
            [
                'cron_ms'           => $result['cron_ms'],
                'submit_claimed'    => $result['submissions']['claimed'],
                'submit_processed'  => $result['submissions']['processed'],
                'submit_lane_ms'    => $result['submissions']['lane_ms'] ?? 0,
                'submit_item_ms'    => $result['submissions']['item_ms'],
                'status_claimed'    => $result['status_checks']['claimed'],
                'status_processed'  => $result['status_checks']['processed'],
                'status_lane_ms'    => $result['status_checks']['lane_ms'] ?? 0,
                'status_item_ms'    => $result['status_checks']['item_ms'],
                'test_errors'       => $result['test_errors_injected'] ?? null,
            ]
        );
    }
}
