<?php


namespace OpenProvider\WhmcsRegistrar\Controllers\System;

use \Exception;

use OpenProvider\API\API;
use OpenProvider\API\Domain;

use WeDevelopCoffee\wPower\Controllers\BaseController;
use WeDevelopCoffee\wPower\Core\Core;

use WHMCS\Domains\DomainLookup\ResultsList;
use WHMCS\Domains\DomainLookup\SearchResult;

/**
 * Class GetDomainSuggestions
 * @package OpenProvider\WhmcsRegistrar\Controllers\System
 */
class DomainSuggestionsController extends BaseController
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
     * @var ResultsList
     */
    private $resultsList;

    /**
     * ConfigController constructor.
     */
    public function __construct(Core $core, Api $API, Domain $domain)
    {
        parent::__construct($core);

        $this->domain = $domain;
        $this->API = $API;

        $this->resultsList = new ResultsList();
    }

    /**
     * @param $params
     * @return ResultsList
     * @throws Exception
     */
    public function get($params)
    {
        $api = $this->API;
        $api->setParams($params);
        $args = [
            'name' => $params['searchTerm'],
            'limit' => 10,
        ];

        $suggestionSettings = $params['suggestionSettings'];
        if (isset($suggestionSettings['preferredLanguage']) && !empty($suggestionSettings['preferredLanguage']))
            $args['language'] = $suggestionSettings['preferredLanguage'];

        if (isset($suggestionSettings['sensitive']) && $suggestionSettings['sensitive'] == 'on')
            $args['sensitive'] = 1;

        if (isset($suggestionSettings['suggestTlds']) && count($suggestionSettings['suggestTlds']) > 0)
            $args['tlds'] = array_map(function ($tld) {
                return mb_substr($tld, 1);
            }, explode(',', $suggestionSettings['suggestTlds']));

        //get suggested domains
        try {
            $suggestedDomains = $api->sendRequest('suggestNameDomainRequest', $args);
        } catch (Exception $e) {
            return $this->resultsList;
        }

        $domains = [];
        foreach ($suggestedDomains as $item) {
            $domain = new Domain();
            $domain->extension  = $item['tld'];
            $domain->name       = $item['domain'];
            $domains[]          = $domain;
        }

        // check domains availability and append to this->resultsList
        $this->checkDomains($domains, $params);
        return $this->resultsList;
    }

    /**
     * method to check domains by 15 per time
     *
     * @param $domains
     * @param $params
     * @return void
     */
    private function checkDomains($domains, $params)
    {
        $api = $this->API;

        try {
            $checkedDomains = $api->checkDomainArray($domains);
        } catch (Exception $e) {
            if($e->getcode() == 307)
            {
                // OP response: "Your domain request contains an invalid extension!""
                // Meaning: the id is not supported.
                foreach($params['tldsToInclude'] as $tld)
                {
                    $domain_tld  = $tld;
                    $domain_sld  = $params['isIdnDomain'] ? $params['punyCodeSearchTerm'] : $params['searchTerm'];
                    $searchResult = new SearchResult($domain_sld, $domain_tld);
                    $searchResult->setStatus(SearchResult::STATUS_TLD_NOT_SUPPORTED);
                    $this->resultsList->append($searchResult);
                }
                return;
            }
            \logModuleCall('openprovider', 'whois', $domains, $e->getMessage(), null, [$params['Password']]);
            return;
        }

        foreach($checkedDomains as $domain_status)
        {
            $domain_sld = explode('.', $domain_status['domain'])[0];
            $domain_tld = str_replace($domain_sld . '.', '', $domain_status['domain']);

            $searchResult = new SearchResult($domain_sld, $domain_tld);
            if($params['OpenproviderPremium'] == 'on' && isset($domain_status['premium']) && $domain_status['status'] == 'free')
            {
                $status = SearchResult::STATUS_NOT_REGISTERED;
                $searchResult->setPremiumDomain(true);

                $args['domain']['name']      = $domain_sld;
                $args['domain']['extension'] = $domain_tld;
                $args['operation']           = 'create';
                try {
                    $create_pricing              = $api->sendRequest('retrievePriceDomainRequest', $args);
                } catch (Exception $e) {
                    continue;
                }

                $args['operation'] = 'transfer';
                try {
                    $transfer_pricing  = $api->sendRequest('retrievePriceDomainRequest', $args);
                } catch (Exception $e) {
                    continue;
                }

                // Retrieve the pricing
                $searchResult->setPremiumCostPricing(
                    array(
                        'register'  => $create_pricing['price']['reseller']['price'],
                        'renew'     =>  $transfer_pricing['price']['reseller']['price'],
                        'CurrencyCode' => $create_pricing['price']['reseller']['currency'],
                    )
                );
            }
            elseif($domain_status['status'] == 'free')
                $status = SearchResult::STATUS_NOT_REGISTERED;
            else
                $status = SearchResult::STATUS_REGISTERED;

            $searchResult->setStatus($status);

            $this->resultsList->append($searchResult);
        }
    }
}
