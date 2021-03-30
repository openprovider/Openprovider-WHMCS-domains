<?php
namespace OpenProvider\WhmcsRegistrar\Controllers\Hooks;

use OpenProvider\API\ApiHelper;
use OpenProvider\WhmcsRegistrar\src\Configuration;
use OpenProvider\API\Domain as api_domain;
use WeDevelopCoffee\wPower\Models\Domain;

/**
 * Class DnsNotificationController
 * OpenProvider Registrar module
 *
 * @copyright Copyright (c) Openprovider 2018
 */

class DnsNotificationController
{
    protected $api_domain;
    /**
     * @var Domain
     */
    private $domain;
    /**
     * @var ApiHelper
     */
    private $apiHelper;

    protected $op_nameservers = [
        'ns1.openprovider.nl',
        'ns2.openprovider.be',
        'ns3.openprovider.eu'
    ];

    /**
     * ConfigController constructor.
     */
    public function __construct(api_domain $api_domain, Domain $domain, ApiHelper $apiHelper)
    {
        $this->api_domain = $api_domain;
        $this->domain = $domain;
        $this->apiHelper = $apiHelper;
    }

    /**
     * @param $params
     * @return string[]|void
     */
    public function notify ($params)
    {
        $domain = $this->domain->find($params['domainid']);
        if($domain->registrar != 'openprovider' || Configuration::getOrDefault('require_op_dns_servers', true) != true)
            return;

        try {

            $op_api_domain = $this->api_domain;
            $op_api_domain->load(array(
                'name'      => str_replace('.' . $domain->getTldAttribute(), '', $domain->domain),
                'extension' => $domain->getTldAttribute()
            ));

            $op_domain = $this->apiHelper->getDomain($op_api_domain);

            $notOpenproviderNameservers = [];
            foreach ($op_domain['nameServers'] as $nameserver) {
                if (!in_array($nameserver['name'], $this->op_nameservers)) {
                    $notOpenproviderNameservers[] = $nameserver['name'];
                }
            }

            $conditionDisplayAlert = (count($op_domain['nameServers'])
                    - count($notOpenproviderNameservers)) < 2;

            if ($conditionDisplayAlert) {
                $notOpenproviderNameserversString = implode(', ', $notOpenproviderNameservers);
                $error_message = "
                    The domain “{$domain->domain}” is currently assigned the following nameservers: 
                    {$notOpenproviderNameserversString}. 
                    You will need to assign the nameservers “ns1.openprovider.nl”, “ns2.openprovider.be” and “ns3.openprovider.eu” 
                    to your domain for the below DNS records to take effect.";

                return [
                    'additionalClasses' => 'alert-danger',
                    'type'  => 'error',
                    'errorshtml' => $error_message,
                ];
            }
        } catch (\Exception $e) {}
    }
}
