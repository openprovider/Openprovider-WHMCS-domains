<?php

namespace OpenProvider\WhmcsRegistrar\Controllers\System;

use Exception;
use OpenProvider\API\ApiHelper;
use OpenProvider\API\ApiInterface;
use OpenProvider\WhmcsRegistrar\src\Notification;
use OpenProvider\OpenProvider as OP;
use WHMCS\Database\Capsule;
use WeDevelopCoffee\wPower\Controllers\BaseController;
use WeDevelopCoffee\wPower\Core\Core;
use OpenProvider\API\Domain;

/**
 * Class IdProtect
 * @package OpenProvider\WhmcsRegistrar\Controllers\System
 */
class IdProtectController extends BaseController
{
    /**
     * @var Domain
     */
    private $domain;
    /**
     * @var ApiInterface
     */
    private $apiClient;

    /**
     * ConfigController constructor.
     */
    public function __construct(Core $core, Domain $domain, ApiInterface $apiClient)
    {
        parent::__construct($core);

        $this->apiClient = $apiClient;
        $this->domain    = $domain;
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

        $OpenProvider       = new OP($params, $this->apiClient);
        $op_domain_obj      = $OpenProvider->domain($domain->domain);
        $opDomainResponse = $this->apiClient->call('searchDomainRequest', [
            'domainNamePattern' => $op_domain_obj->name,
            'extension' => $op_domain_obj->extension,
        ]);

        if (!$opDomainResponse->isSuccess()) {
            \logModuleCall('OpenProvider', 'Save identity toggle',$params['domainname'], [$OpenProvider->domain, @$op_domain, $OpenProvider], $opDomainResponse->getMessage(), [$params['Password']]);

            if($opDomainResponse->getMessage() == 'Wpp contract is not signed')
            {
                $notification = new Notification();
                $notification->WPP_contract_unsigned_one_domain($params['domainname'])
                    ->send_to_admins();
            }

            return array(
                'error' => $opDomainResponse->getMessage(),
            );
        }

        $opDomain = $opDomainResponse->getData()['results'][0];
        $OpenProvider->toggle_whois_protection($domain, $op_domain_obj, $opDomain);

        return array(
            'success' => 'success',
        );
    }
}
