<?php

namespace OpenProvider\WhmcsDomainAddon\Controllers\System;

use WeDevelopCoffee\wPower\Core\Core;
use OpenProvider\WhmcsDomainAddon\Services\BulkTransfer\BulkTransferProcessor;

class BulkTransferCronController extends BaseController
{
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
        $limit = isset($params['limit']) ? (int) $params['limit'] : 10; // limit should be based on crone setup time

        $this->start();
        $result = $this->bulkTransferProcessor->processQueuedItems($limit);
        $this->printDebug(sprintf(
            'Bulk transfer cron claimed %d items and processed %d items.',
            $result['claimed'],
            $result['processed']
        ));
        $this->shutdown('Bulk transfer queue processed');

        return $result;
    }
}
