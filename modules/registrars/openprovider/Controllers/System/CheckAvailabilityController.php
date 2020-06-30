<?php
namespace OpenProvider\WhmcsRegistrar\Controllers\System;

use Exception;
use OpenProvider\API\API;
use OpenProvider\API\Domain;
use WeDevelopCoffee\wPower\Controllers\BaseController;
use WeDevelopCoffee\wPower\Core\Core;
use WHMCS\Domains\DomainLookup\ResultsList;
use WHMCS\Domains\DomainLookup\SearchResult;

/**
 * Class CheckAvailabilityController
 * @package OpenProvider\WhmcsRegistrar\Controllers\System
 */
class CheckAvailabilityController  extends BaseController
{
    /**
     * @var API
     */
    private $API;
    /**
     * @var ResultsList
     */
    private $resultsList;
    /**
     * @var Domain
     */
    private $domain;

    /**
     * ConfigController constructor.
     */
    public function __construct(Core $core, API $API, Domain $domain, $resultsList = '')
    {
        parent::__construct($core);

        $this->API = $API;
        $this->domain = $domain;

        /**
         * Throws the following error when trying to auto inject the class. Still optional for tests.
         * Method DI\Definition\ObjectDefinition::__toString() must not throw an exception, caught Error: Call to undefined method DI\Definition\ObjectDefinition::getScope() in
         */
        $this->resultsList = new ResultsList();
    }

    /**
     * @param $params
     * @return ResultsList
     * @throws Exception
     */
    public function check($params)
    {
        // Safety feature: Make premium opt-in with warning only. See https://requests.whmcs.com/topic/major-bug-premium-domains-billed-incorrectly.
        if(isset($params['OpenproviderPremium']) && $params['OpenproviderPremium'] == 'on')
        {
            $premiumEnabled = (bool) $params['premiumEnabled'];
        }
        else
            $premiumEnabled = false;

        $results = $this->resultsList;
        if(empty($params['tldsToInclude']))
            return $results;

        $api = $this->API;
        $api->setParams($params);

        $domains = [];
        foreach($params['tldsToInclude'] as $tld)
        {
            $domain             = clone $this->domain;
            $domain->extension  = substr($tld, 1);
            $domain->name       = $params['isIdnDomain'] ? $params['punyCodeSearchTerm'] : $params['searchTerm'];
            $domains[]          = $domain;
        }

        try {
            $status =  $api->checkDomainArray($domains);
        } catch (Exception $e) {
            if($e->getcode() == 307)
            {
                // OP response: "Your domain request contains an invalid extension!""
                // Meaning: the id is not supported.

                foreach($params['tldsToInclude'] as $tld)
                {
                    $domain_tld  = substr($tld, 1);
                    $domain_sld  = $params['isIdnDomain'] ? $params['punyCodeSearchTerm'] : $params['searchTerm'];
                    $searchResult = new SearchResult($domain_sld, $domain_tld);
                    $searchResult->setStatus(SearchResult::STATUS_TLD_NOT_SUPPORTED);
                    $results->append($searchResult);
                }
                return $results;
            }
            else
            {
                \logModuleCall('openprovider', 'whois', $domains, $e->getMessage(), null, [$params['Password']]);
                return $results;
            }
        }

        foreach($status as $domain_status)
        {
            $domain_sld = explode('.', $domain_status['domain'])[0];
            $domain_tld = str_replace($domain_sld . '.', '', $domain_status['domain']);

            $searchResult = new SearchResult($domain_sld, $domain_tld);

            if(isset($domain_status['premium']) && $domain_status['status'] == 'free')
            {
                if($premiumEnabled == false)
                    $status = SearchResult::STATUS_RESERVED;
                else
                {
                    $status = SearchResult::STATUS_NOT_REGISTERED;
                    $searchResult->setPremiumDomain(true);

                    $args['domain']['name']         = $domain_sld;
                    $args['domain']['extension']    = $domain_tld;
                    $args['operation']    = 'create';
                    $create_pricing = $api->sendRequest('retrievePriceDomainRequest', $args);


                    $args['operation']    = 'transfer';
                    $transfer_pricing = $api->sendRequest('retrievePriceDomainRequest', $args);

                    // Retrieve the pricing
                    $searchResult->setPremiumCostPricing(
                        array(
                            'register'  => $create_pricing['price']['reseller']['price'],
                            'renew'     =>  $transfer_pricing['price']['reseller']['price'],
                            'CurrencyCode' => $create_pricing['price']['reseller']['currency'],
                        )
                    );

                }
            }
            elseif($domain_status['status'] == 'free')
                $status = SearchResult::STATUS_NOT_REGISTERED;
            else
                $status = SearchResult::STATUS_REGISTERED;

            $searchResult->setStatus($status);
            $results->append($searchResult);

        }

        return $results;
    }
}