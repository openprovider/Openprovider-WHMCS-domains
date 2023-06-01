<?php

namespace OpenProvider\WhmcsRegistrar\Controllers\Hooks;

use OpenProvider\API\ApiHelper;
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
    /**
     * @var api_domain
     */
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
     *
     * @return string|void
     */
    public function run ($params)
    {
        $domain = $this->domain->find($_REQUEST['domainid']);
        if($domain->registrar != 'openprovider')
            return;

        try {

            $op_api_domain = $this->api_domain;
            $op_api_domain->load(array(
                'name'      => str_replace('.' . $domain->getTldAttribute(), '', $domain->domain),
                'extension' => $domain->getTldAttribute()
            ));

            $op_domain = $this->apiHelper->getDomain($op_api_domain);

            foreach($op_domain['nameServers'] as $nameserver)
            {
                if(in_array($nameserver['name'], $this->op_nameservers))
                {
                    return <<< EOF
<script type="text/javascript">
    jQuery("select[name='dnsrecordtype[]']")
    .each(function(){
        if(jQuery(this).val() == 'NS' || jQuery(this).val() == 'SOA')
      {
        var element = jQuery(this).parent().parent().find('input, textarea, button, select');
        // we need to clone this element without disabled attribute
        // to send this value into controller 
        var elementClone = element.clone();
          element.attr('disabled','disabled');
          element.parent().append(elementClone.attr('hidden', 'hidden'))
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
