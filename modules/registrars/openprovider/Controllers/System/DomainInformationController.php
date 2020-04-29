<?php
namespace OpenProvider\WhmcsRegistrar\Controllers\System;

use WHMCS\Carbon;
use WHMCS\Domain\Registrar\Domain;
use WeDevelopCoffee\wPower\Controllers\BaseController;
use WeDevelopCoffee\wPower\Core\Core;
use OpenProvider\API\API;
use OpenProvider\API\Domain as api_domain;

/**
 * Class DomainInformationController
 */
class DomainInformationController extends BaseController
{
    /**
     * @var API
     */
    private $API;
    /**
     * @var Domain
     */
    private $api_domain;

    /**
     * ConfigController constructor.
     */
    public function __construct(Core $core, API $API, api_domain $api_domain)
    {
        parent::__construct($core);

        $this->API = $API;
        $this->api_domain = $api_domain;
    }

    /**
     * Get the nameservers.
     *
     * @param $params
     * @return array
     */
    function get($params)
    {
        $params['sld'] = $params['original']['domainObj']->getSecondLevel();
        $params['tld'] = $params['original']['domainObj']->getTopLevel();

        // Launch API
        try {
            $api                =   $this->API;
            $api->setParams($params);
            $domain             =   $this->api_domain;
            $domain->load(array (
                'name' => $params['sld'],
                'extension' => $params['tld']
            ));
        } catch (\Exception $e) {
            return array
            (
                'error' => $e->getMessage(),
            );
        }

        // Get the data
        $op_domain                  = $api->retrieveDomainRequest($domain, true);
        $response = [];
        $response['domain']         = $op_domain['domain']['name'] . '.' . $op_domain['domain']['extension'];
        $response['tld']            = $op_domain['domain']['extension'];
        $response['nameservers']    = $this->getNameservers($api, $domain);
        $response['status']         = api_domain::convertOpStatusToWhmcs($op_domain['status']);
        $response['transferlock']   = ($op_domain['isLocked'] == 0 ? false : true);
        $response['expirydate']     = $op_domain['expirationDate'];
        $response['addons']['hasidprotect'] = ($op_domain['isPrivateWhoisEnabled'] == '1' ? true : false);

        return (new Domain)
            ->setDomain($domain)
            ->setNameservers($response['nameservers'])
            ->setRegistrationStatus($response['status'])
            ->setTransferLock($response['transferlock'])
            ->setExpiryDate(Carbon::createFromFormat('Y-m-d H:i:s', $response['expirydate']), 'Europe/Amsterdam') // $response['expirydate'] = YYYY-MM-DD
            ->setIdProtectionStatus($response['addons']['hasidprotect']);
    }

    /**
     * @param API $api
     * @param Domain $domain
     * @return array
     */
    private function getNameservers(API $api, api_domain $domain): array
    {
        $nameservers = $api->getNameservers($domain, true);
        $return = array ();
        $i = 1;

        foreach ($nameservers as $ns) {
            $return['ns' . $i] = $ns;
            $i++;
        }
        return $return;
    }

}