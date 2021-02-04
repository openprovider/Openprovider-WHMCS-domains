<?php

namespace OpenProvider\WhmcsRegistrar\Controllers\System;

use Exception;
use OpenProvider\API\JsonAPI;
use OpenProvider\WhmcsRegistrar\src\Notification;
use OpenProvider\WhmcsRegistrar\src\OpenProvider as OP;
use WHMCS\Database\Capsule;
use WeDevelopCoffee\wPower\Controllers\BaseController;
use WeDevelopCoffee\wPower\Core\Core;
use OpenProvider\API\API;
use OpenProvider\API\Domain;

/**
 * Class IdProtect
 * @package OpenProvider\WhmcsRegistrar\Controllers\System
 */
class IdProtectController extends BaseController
{
    /**
     * @var API
     */
    private $API;
    /**
     * @var Domain
     */
    private $domain;

    /**
     * ConfigController constructor.
     */
    public function __construct(Core $core, JsonAPI $API, Domain $domain)
    {
        parent::__construct($core);

        $this->API = $API;
        $this->domain = $domain;
    }

    /**
     * Toggle the id protection.
     *
     * @param $params
     * @return array
     */
    public function toggle($params)
    {
        $params['sld'] = $params['original']['domainObj']->getSecondLevel();
        $params['tld'] = $params['original']['domainObj']->getTopLevel();
        $params['domainname'] = $params['sld'] . '.' . $params['tld'];

        // Get the domain details
        $domain = Capsule::table('tbldomains')
            ->where('id', $params['domainid'])
            ->get()[0];

        if(isset($params['protectenable']))
            $domain->idprotection = $params['protectenable'];

        try {
            $OpenProvider       = new OP();
            $op_domain_obj      = $OpenProvider->domain($domain->domain);
            $this->API->setParams($params);
            $op_domain          = $this->API->getDomainRequest($op_domain_obj);
            $OpenProvider->toggle_whois_protection($domain, $op_domain, $this->API);

            return array(
                'success' => 'success',
            );
        } catch (Exception $e) {
            \logModuleCall('OpenProvider', 'Save identity toggle',$params['domainname'], [$OpenProvider->domain, @$op_domain, $OpenProvider], $e->getMessage(), [$params['Password']]);

            if($e->getMessage() == 'Wpp contract is not signed')
            {
                $notification = new Notification();
                $notification->WPP_contract_unsigned_one_domain($params['domainname'])
                    ->send_to_admins();

            }

            return array(
                'error' => $e->getMessage(),
            );
        }
    }
}