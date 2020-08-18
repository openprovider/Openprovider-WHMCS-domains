<?php
namespace OpenProvider\WhmcsDomainAddon\Controllers\Hooks;

use Carbon\Carbon;
use WeDevelopCoffee\wDomainAbuseMonitor\App\Domain as Domain_App;
use WeDevelopCoffee\wDomainAbuseMonitor\Models\Domain;
use WeDevelopCoffee\wPower\Controllers\BaseController;
use WeDevelopCoffee\wPower\Core\Core;
use \WHMCS\Module\Addon\Setting;

/**
 * Class DomainRblClientDetailsAdminHookController
 * @package WeDevelopCoffee\wDomainAbuseMonitor\Controllers\Hooks
 */
class BasePermissionController extends BaseController
{

    /**
     * Check if the user has access.
     * @return bool
     */
    protected function checkPermission()
    {
        $access_settings = explode(',', $this->getAccessSettings());

        // Has access
        if(in_array($GLOBALS['aInt']->getAdminRoleID(), $access_settings))
            return true;

        // No access
        return false;
    }

    /**
     * Get the global do not suspend setting.
     * @return boolean
     */
    protected function getAccessSettings()
    {
        return Setting::where('module', 'domain_abuse_monitor')
            ->where('setting', 'access')->first()->value;
    }

    /**
     * Get the global do not suspend setting.
     * @return boolean
     */
    protected function getRoles()
    {
        return Setting::where('module', 'domain_abuse_monitor')
            ->where('setting', 'access')->first()->value;
    }
}