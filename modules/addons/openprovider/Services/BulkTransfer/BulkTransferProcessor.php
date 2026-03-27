<?php

namespace OpenProvider\WhmcsDomainAddon\Services\BulkTransfer;

class BulkTransferProcessor
{
    public function __construct() {
    }

    public function processQueuedItems($limit = 25)
    {
        $processed = 0;
        $claimed = 0;

        return [
            'claimed' => $claimed,
            'processed' => $processed,
        ];
    }
}
