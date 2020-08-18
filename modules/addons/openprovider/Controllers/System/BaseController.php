<?php
namespace OpenProvider\WhmcsDomainAddon\Controllers\System;

use WeDevelopCoffee\wPower\Controllers\BaseController as wpower_BaseController;
use \WHMCS\Module\Addon\Setting;

/**
 * Class ContactControllerView
 * @package WeDevelopCoffee\wDomainAbuseMonitor\Controllers\System
 */
class BaseController extends wpower_BaseController
{
    /**
     * Log the start of the activity.
     */
    protected function start()
    {
        $activity = 'Openprovider addon - START';

        $this->printDebug($activity);
    }

    /**
     * Log the end of the activity.
     * @param string $string
     */
    protected function shutdown(string $string = 'Done')
    {
        $activity = 'Openprovider addon - STOP - ' . $string;

        $this->printDebug($activity);
    }

    /**
     * Log the activities in WHMCS.
     * @param string $activity
     * @return array
     */
    protected function logActivity(string $activity): array
    {
        $command = 'LogActivity';
        $postData = array (
            'description' => $activity,
        );

        return localAPI($command, $postData);
    }

    /**
     * @param string $activity
     */
    protected function printDebug(string $activity): void
    {
        if (defined('OP_ADDON_DEBUG')) {
            echo date("H:i:s") . " - " . $activity . "\n";
        }
    }
}