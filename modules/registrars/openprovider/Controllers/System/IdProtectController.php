<?php

namespace OpenProvider\WhmcsRegistrar\Controllers\System;

use Exception;
use OpenProvider\API\JsonAPI;
use OpenProvider\OpenProvider;

use OpenProvider\WhmcsRegistrar\src\Notification;

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
     * @var OpenProvider
     */
    private $openProvider;
    /**
     * @var Domain
     */
    private $domain;

    /**
     * ConfigController constructor.
     */
    public function __construct(Core $core, Domain $domain)
    {
        parent::__construct($core);

        $this->openProvider = new OpenProvider();
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
        $api = $this->openProvider->getApi();

        // Get the domain details
        $domain = Capsule::table('tbldomains')
            ->where('id', $params['domainid'])
            ->get()[0];

        if(isset($params['protectenable']))
            $domain->idprotection = $params['protectenable'];

        try {
            $this->domain      = $this->openProvider->domain($domain->domain);
            $op_domain          = $api->getDomainRequest($this->domain);
            $this->openProvider->toggle_whois_protection($domain, $op_domain);

            return array(
                'success' => 'success',
            );
        } catch (Exception $e) {
            \logModuleCall('OpenProvider', 'Save identity toggle', $this->domain->getFullName(), [$this->domain, @$op_domain, $this->openProvider], $e->getMessage(), [$params['Password']]);

            if($e->getMessage() == 'Wpp contract is not signed')
            {
                $notification = new Notification();
                $notification->WPP_contract_unsigned_one_domain($this->domain->getFullName())
                    ->send_to_admins();

            }

            return array(
                'error' => $e->getMessage(),
            );
        }
    }
}