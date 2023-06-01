<?php

namespace OpenProvider\API;

/**
 * Class APIConfig
 * @package OpenProvider\API* OpenProvider Registrar module
 *
 * @copyright Copyright (c) Openprovider 2018
 */

class APIConfig
{
    static public $moduleVersion     = 'whmcs-5.6-REST';
    static public $supportedDnsTypes = array('A', 'AAAA', 'CAA', 'CNAME', 'MX', 'SPF', 'SSHFP', 'SRV', 'TLSA', 'TXT');
    static public $dnsRecordTtl      = 86400;
    static public $dnsRecordPriority = 10;
    static public $autoRenew         = 'on';
    static public $encoding          = 'UTF-8';
    static public $curlTimeout       = 1000;
    static public $defaultGender     = \OpenProvider\API\CustomerGender::MALE;
    static public $handlesNames = [
        'ownerHandle'    => 'Owner',
        'billingHandle'  => 'Billing',
        'adminHandle'    => 'Admin',
        'techHandle'     => 'Tech',
        'resellerHandle' => 'Reseller'
    ];
    static public $nameservers = [
        'ns1.openprovider.nl',
        'ns2.openprovider.be',
        'ns3.openprovider.eu'
    ];

    /**
     * Check what is generating the API call.
     *
     * @return string
     */
    public static function getInitiator()
    {
        if(strpos($_SERVER['SCRIPT_NAME'], 'api.php'))
            return 'api';
        elseif(isset($_SESSION['adminid']))
            return 'admin';
        elseif(isset($_SESSION['uid']))
            return 'customer';
        else
            return 'system';
    }

    /**
     * Get the module version.
     * @return string|string[]
     */
    public static function getModuleVersion()
    {
        $moduleVersion = str_replace('whmcs-', 'v', self::$moduleVersion);
        return $moduleVersion;
    }

    /**
     * @return array
     */
    public static function getDefaultNameservers(): array
    {
        return self::$nameservers;
    }
}
