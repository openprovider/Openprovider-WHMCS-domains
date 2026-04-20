<?php

namespace OpenProvider\WhmcsDomainAddon\Controllers\System;

use WeDevelopCoffee\wPower\Core\Core;
use OpenProvider\WhmcsDomainAddon\Services\BulkTransfer\BulkTransferProcessor;

class BulkTransferCronController extends BaseController
{
    private const DEFAULT_SUBMIT_LIMIT = 10;
    private const DEFAULT_STATUS_LIMIT = 50;

    /**
     * @var BulkTransferProcessor
     */
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
        $lane = $this->normalizeLane($params['lane'] ?? 'all');
        $submitLimit = isset($params['submit_limit'])
            ? (int) $params['submit_limit']
            : (isset($params['limit']) ? (int) $params['limit'] : self::DEFAULT_SUBMIT_LIMIT);
        $statusLimit = isset($params['status_limit']) ? (int) $params['status_limit'] : self::DEFAULT_STATUS_LIMIT;

        $submitLimit = max(1, $submitLimit);
        $statusLimit = max(1, $statusLimit);

        $this->start();

        $result = [
            'lane' => $lane,
            'status_checks' => [
                'claimed' => 0,
                'processed' => 0,
            ],
            'submissions' => [
                'claimed' => 0,
                'processed' => 0,
            ],
        ];

        if (in_array($lane, ['all', 'status'], true)) {
            $result['status_checks'] = $this->bulkTransferProcessor->processPendingTransferItems($statusLimit);
            $this->printDebug(sprintf(
                'Bulk transfer cron claimed %d items and processed %d items for status update.',
                $result['status_checks']['claimed'],
                $result['status_checks']['processed']
            ));
        }

        if (in_array($lane, ['all', 'submit'], true)) {
            $result['submissions'] = $this->bulkTransferProcessor->processQueuedItems($submitLimit);
            $this->printDebug(sprintf(
                'Bulk transfer cron claimed %d items and processed %d items for transfer submission.',
                $result['submissions']['claimed'],
                $result['submissions']['processed']
            ));
        }

        $this->shutdown('Bulk transfer cron processed');

        return $result;
    }

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
}
