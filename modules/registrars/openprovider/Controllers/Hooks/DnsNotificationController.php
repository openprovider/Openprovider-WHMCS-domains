<?php
namespace OpenProvider\WhmcsRegistrar\Controllers\Hooks;

use OpenProvider\OpenProvider;
use WeDevelopCoffee\wPower\Models\Registrar;
use WeDevelopCoffee\wPower\Core\Core;
use OpenProvider\API\API;
use OpenProvider\API\Domain as api_domain;
use WeDevelopCoffee\wPower\Models\Domain;


/**
 * Class DnsNotificationController
 * OpenProvider Registrar module
 *
 * @copyright Copyright (c) Openprovider 2018
 */

class DnsNotificationController{

    protected $API;
    protected $api_domain;
    /**
     * @var Domain
     */
    private $domain;

    protected $op_nameservers = [
        'ns1.openprovider.nl',
        'ns2.openprovider.be',
        'ns3.openprovider.eu'
    ];

    /**
     * ConfigController constructor.
     */
    public function __construct(Core $core, API $API, api_domain $api_domain, Domain $domain)
    {
        $this->API = $API;
        $this->api_domain = $api_domain;
        $this->domain = $domain;
    }

    /**
    * 
    * 
    * @return 
    */
    public function notify ($params)
    {
        $domain = $this->domain->find($params['domainid']);
        if($domain->registrar != 'openprovider' || Registrar::getByKey('openprovider','require_op_dns_servers', 'on') != 'on')
            return;

        $openprovider = new OpenProvider();

        try {

            $op_api_domain             =   $this->api_domain;
            $op_api_domain->load(array (
                'name' => str_replace('.'.$domain->getTldAttribute(), '', $domain->domain),
                'extension' => $domain->getTldAttribute()
            ));

            $op_domain                  = $openprovider->api->retrieveDomainRequest($op_api_domain, true);

            foreach($op_domain['nameServers'] as $nameserver)
            {
                if(!in_array($nameserver['name'], $this->op_nameservers))
                {
                    $error_message = 'Please configure the following nameservers before you can change the DNS records: ' . implode(', ', $this->op_nameservers);

                    return [
                        'additionalClasses' => 'alert-danger',
                        'type'  => 'error',
                        'errorshtml' => $error_message,
                    ];
                }
            }

        } catch (\Exception $e) {
            return;
        }

    }
}
