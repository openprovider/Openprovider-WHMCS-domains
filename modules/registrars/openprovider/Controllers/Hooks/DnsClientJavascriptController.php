<?php

namespace OpenProvider\WhmcsRegistrar\Controllers\Hooks;

use OpenProvider\API\JsonAPI;
use OpenProvider\OpenProvider;
use WeDevelopCoffee\wPower\Core\Core;
use OpenProvider\API\Domain as api_domain;
use WeDevelopCoffee\wPower\Models\Domain;


/**
 * Class DnsClientJavascriptController
 * OpenProvider Registrar module
 *
 * @copyright Copyright (c) Openprovider 2018
 */
class DnsClientJavascriptController
{

    protected $API;
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
    public function __construct(Core $core, JsonAPI $API, api_domain $api_domain, Domain $domain)
    {
        $this->API        = $API;
        $this->api_domain = $api_domain;
        $this->domain     = $domain;
    }

    /**
     *
     *
     * @return
     */
    public function run($params)
    {
        $domain = $this->domain->find($_REQUEST['domainid']);
        if ($domain->registrar != 'openprovider')
            return;

        $openprovider = new OpenProvider();

        try {
            $openprovider->api->setParams($params);
            $op_api_domain = $this->api_domain;
            $op_api_domain->load(array(
                'name'      => str_replace('.' . $domain->getTldAttribute(), '', $domain->domain),
                'extension' => $domain->getTldAttribute()
            ));

            $op_domain = $openprovider->api->getDomainRequest($op_api_domain);

            foreach ($op_domain['name_servers'] as $nameserver) {
                if (in_array($nameserver['name'], $this->op_nameservers)) {
                    return <<< EOF
<script type="text/javascript">
    jQuery("select[name='dnsrecordtype[]']")
    .each(function(){
        if(jQuery(this).val() == 'NS' || jQuery(this).val() == 'SOA')
      {
        jQuery(this).parent().parent().find('input, textarea, button, select').attr('disabled','disabled');
        console.log('FOUND NS');
      }
    });
</script>
                    
EOF;
                }
            }

        } catch (\Exception $e) {
            return;
        }

    }
}
