<?php

namespace OpenProvider\WhmcsDomainAddon\Services\BulkTransfer;

/**
 * Test harness for bulk transfer cron performance evaluation.
 *
 * Enables realistic API timing measurements against real ResellerClub / OP
 * endpoints using a single proxy domain, while returning randomised outcomes
 * for every non-real domain in the batch so the full cron pipeline can be
 * exercised without touching actual registrar data.
 *
 * Activate via:  php BulkTransfer.php --test-mode
 *
 * Behaviour per API step
 * ──────────────────────
 * unlock / getEpp  → calls real RC API using REAL_DOMAIN_ID for timing,
 *                    then returns a random success or error.
 *                    Combined error budget ≤ ERROR_BUDGET across the run.
 *
 * transferDomain   → calls real OP API with the stored EPP code (empty if
 *                    none was captured yet), ignores the actual response, and
 *                    returns a weighted random transfer state so both the
 *                    submit and the status lane get work to do.
 *
 * getDomainDetails → calls real OP API with REAL_DOMAIN for latency sampling,
 *                    ignores the real response, returns random state.
 *
 * If the item domain IS REAL_DOMAIN every step runs normally and the actual
 * API response is used unchanged.
 */
class BulkTransferTestHarness
{
    const REAL_DOMAIN    = 'op-whmcs.co.in';
    const REAL_DOMAIN_ID = 77;

    /**
     * Max errors allowed across unlock + EPP calls combined for the whole run.
     * Keeps most items flowing through to the transfer stage.
     */
    const ERROR_BUDGET = 9;

    private static $instance = null;

    private $errorCount  = 0;
    private $realEppCode = null;

    private function __construct() {}

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public static function reset(): void
    {
        self::$instance = null;
    }

    // ── Domain identity ──────────────────────────────────────────────────────

    public function isRealDomain($domain): bool
    {
        return strtolower(trim((string) $domain)) === self::REAL_DOMAIN;
    }

    // ── EPP code storage ─────────────────────────────────────────────────────

    public function storeEppCode(string $code): void
    {
        if ($this->realEppCode === null && $code !== '') {
            $this->realEppCode = $code;
        }
    }

    public function getStoredEppCode(): ?string
    {
        return $this->realEppCode;
    }

    // ── Random outcomes ──────────────────────────────────────────────────────

    /**
     * Returns null on success or an error message string.
     * ~7 % error probability; respects global error budget.
     */
    public function randomUnlockOutcome(): ?string
    {
        if ($this->errorCount >= self::ERROR_BUDGET) {
            return null;
        }
        if (mt_rand(1, 14) !== 1) {
            return null;
        }
        $this->errorCount++;
        $messages = [
            '[TEST] Unlock failed: registrar lock could not be removed.',
            '[TEST] Unlock failed: domain is in pendingUpdate status at registrar.',
            '[TEST] Unlock failed: ResellerClub API connection timed out.',
        ];
        return $messages[array_rand($messages)];
    }

    /**
     * Returns null on success or an error message string.
     * ~4 % error probability; respects global error budget.
     */
    public function randomEppOutcome(): ?string
    {
        if ($this->errorCount >= self::ERROR_BUDGET) {
            return null;
        }
        if (mt_rand(1, 25) !== 1) {
            return null;
        }
        $this->errorCount++;
        $messages = [
            '[TEST] EPP retrieval failed: auth code not available from registrar.',
            '[TEST] EPP retrieval failed: domain requires manual auth code reset.',
            '[TEST] EPP retrieval failed: registrar returned empty auth code.',
        ];
        return $messages[array_rand($messages)];
    }

    /**
     * Generates a plausible-looking mock EPP code for non-real domains.
     */
    public function mockEppCode(): string
    {
        return 'TST-' . strtoupper(substr(md5(uniqid('', true)), 0, 10));
    }

    /**
     * Returns a normalised transfer state (same structure as
     * OpenproviderTransferClient::normalizeTransferState output).
     *
     * Weight distribution keeps plenty of items in a pending state so
     * processPendingTransferItems gets realistic work on the next cron run:
     *   REQ 40 % | PEN 30 % | SCH 15 % | ACT 10 % | FAI 5 %
     */
    public function randomTransferState(): array
    {
        $roll = mt_rand(1, 100);
        if ($roll <= 40)     $status = 'REQ';
        elseif ($roll <= 70) $status = 'PEN';
        elseif ($roll <= 85) $status = 'SCH';
        elseif ($roll <= 95) $status = 'ACT';
        else                 $status = 'FAI';

        $renewal = date('Y-m-d H:i:s', mktime(0, 0, 0, (int)date('m'), (int)date('d'), (int)date('Y') + 1));

        return [
            'status'          => $status,
            'domain_id'       => mt_rand(1000000, 9999999),
            'renewal_date'    => $renewal,
            'expiration_date' => $renewal,
            'message'         => '[TEST] Mock transfer state: ' . $status,
            'raw'             => [],
        ];
    }

    /**
     * Returns a normalised domain-details state for the status-check lane.
     *
     * Weighted towards completion so a fraction of pending items finalise:
     *   ACT 35 % | REQ 30 % | PEN 20 % | FAI 10 % | REJ 5 %
     */
    public function randomDomainDetailsState(): array
    {
        $roll = mt_rand(1, 100);
        if ($roll <= 35)     $status = 'ACT';
        elseif ($roll <= 65) $status = 'REQ';
        elseif ($roll <= 85) $status = 'PEN';
        elseif ($roll <= 95) $status = 'FAI';
        else                 $status = 'REJ';

        $renewal = date('Y-m-d H:i:s', mktime(0, 0, 0, (int)date('m'), (int)date('d'), (int)date('Y') + 1));

        return [
            'status'          => $status,
            'domain_id'       => mt_rand(1000000, 9999999),
            'renewal_date'    => $renewal,
            'expiration_date' => $renewal,
            'message'         => '[TEST] Mock domain-details state: ' . $status,
            'raw'             => [],
        ];
    }

    // ── Diagnostics ──────────────────────────────────────────────────────────

    public function getErrorCount(): int
    {
        return $this->errorCount;
    }
}
