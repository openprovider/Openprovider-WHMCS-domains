<?php

namespace OpenProvider\WhmcsRegistrar\Controllers\System;

use OpenProvider\API\ApiHelper;
use OpenProvider\API\ApiInterface;
use OpenProvider\WhmcsRegistrar\helpers\DomainFullNameToDomainObject;
use OpenProvider\WhmcsRegistrar\src\Notification;
use WHMCS\Database\Capsule;
use WeDevelopCoffee\wPower\Controllers\BaseController;
use WeDevelopCoffee\wPower\Core\Core;

/**
 * Class IdProtect
 * @package OpenProvider\WhmcsRegistrar\Controllers\System
 */
class IdProtectController extends BaseController
{
    /**
     * @var ApiInterface
     */
    private $apiHelper;

    /**
     * ConfigController constructor.
     */
    public function __construct(Core $core, ApiHelper $apiHelper)
    {
        parent::__construct($core);

        $this->apiHelper = $apiHelper;
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

        $op_domain_obj      = DomainFullNameToDomainObject::convert($domain->domain);
        try {
            $opDomain = $this->apiHelper->getDomain($op_domain_obj);
        } catch (\Exception $e) {
            \logModuleCall('OpenProvider', 'Save identity toggle',$params['domainname'], [$OpenProvider->domain, @$opDomain, $OpenProvider], $e->getMessage(), [$params['Password']]);
            if ($e->getMessage() == 'Wpp contract is not signed') {
                $notification = new Notification();
                $notification->WPP_contract_unsigned_one_domain($params['domainname'])
                    ->send_to_admins();
            }
            return array(
                'error' => $e->getMessage(),
            );
        }

        $this->apiHelper->toggleWhoisProtection($domain, $opDomain);
        return array(
            'success' => 'success',
        );
    }
}
