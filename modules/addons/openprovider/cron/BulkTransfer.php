<?php

require_once 'BaseCron.php';

$params = [];

if (isset($argv) && is_array($argv)) {
    foreach ($argv as $argument) {
        if (preg_match('/^--limit=(\d+)$/', $argument, $matches)) {
            $params['limit'] = (int) $matches[1];
        }

        if (preg_match('/^--submit-limit=(\d+)$/', $argument, $matches)) {
            $params['submit_limit'] = (int) $matches[1];
        }

        if (preg_match('/^--status-limit=(\d+)$/', $argument, $matches)) {
            $params['status_limit'] = (int) $matches[1];
        }

        if (preg_match('/^--lane=([a-z_]+)$/i', $argument, $matches)) {
            $params['lane'] = strtolower($matches[1]);
        }

        if ($argument === '--debug' && !defined('OP_ADDON_DEBUG')) {
            define('OP_ADDON_DEBUG', true);
        }
    }
}

openprovider_addon_launch('system')
    ->output($params, 'BulkTransfer');
