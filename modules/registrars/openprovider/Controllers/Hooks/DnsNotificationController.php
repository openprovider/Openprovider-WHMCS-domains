<?php

namespace OpenProvider\WhmcsRegistrar\Controllers\Hooks;

use OpenProvider\API\JsonAPI;
use OpenProvider\OpenProvider;
use OpenProvider\WhmcsRegistrar\src\Configuration;
use OpenProvider\API\Domain as api_domain;

use WeDevelopCoffee\wPower\Core\Core;
use WeDevelopCoffee\wPower\Models\Domain;


/**
 * Class DnsNotificationController
 * OpenProvider Registrar module
 *
 * @copyright Copyright (c) Openprovider 2018
 */
class DnsNotificationController
{

    /**
     * @var OpenProvider
     */
    protected $openProvider;
    /**
     * @var api_domain
     */
    protected $api_domain;
    protected $op_nameservers = [
        'ns1.openprovider.nl',
        'ns2.openprovider.be',
        'ns3.openprovider.eu'
    ];
    /**
     * @var Domain
     */
    private $domain;

    /**
     * ConfigController constructor.
     */
    public function __construct(Core $core, api_domain $api_domain, Domain $domain)
    {
        $this->openProvider = new OpenProvider();
        $this->api_domain   = $api_domain;
        $this->domain       = $domain;
    }

    /**
     *
     *
     * @return
     */
    public function notify($params)
    {
        $domain = $this->domain->find($params['domainid']);
        if ($domain->registrar != 'openprovider' || Configuration::getOrDefault('require_op_dns_servers', true) != true)
            return;

        $openprovider = new OpenProvider();

        $api = $openprovider->getApi();

        try {

            $op_api_domain = $openprovider->domain($domain->domain);

            $op_domain = $api->getDomainRequest($op_api_domain);

            $notOpenproviderNameservers = [];
            foreach ($op_domain['name_servers'] as $nameserver) {
                if (!in_array($nameserver['name'], $this->op_nameservers)) {
                    $notOpenproviderNameservers[] = $nameserver['name'];
                }
            }

            $conditionDisplayAlert = (count($op_domain['name_servers'])
                    - count($notOpenproviderNameservers)) < 2;
            if ($conditionDisplayAlert) {
                $notOpenproviderNameserversString = implode(', ', $notOpenproviderNameservers);
                $error_message                    = "
                    The domain “{$domain->domain}” is currently assigned the following nameservers: 
                    {$notOpenproviderNameserversString}. 
                    You will need to assign the nameservers “ns1.openprovider.nl”, “ns2.openprovider.be” and “ns3.openprovider.eu” 
                    to your domain for the below DNS records to take effect.";

                return [
                    'additionalClasses' => 'alert-danger',
                    'type'              => 'error',
                    'errorshtml'        => $error_message,
                ];
            }

            return;
        } catch (\Exception $e) {
            return;
        }
    }
}
