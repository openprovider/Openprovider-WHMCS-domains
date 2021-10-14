<?php

namespace OpenProvider\WhmcsRegistrar\Controllers\System;

use \Exception;

use OpenProvider\API\ApiInterface;
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
    private const SUGGESTION_DOMAIN_NAME_COUNT = 9;

    private const SUGGESTION_DOMAINS_COUNT_FROM_PLACEMENT_PLUS_LIVE = 1;
    private const SUGGESTION_DOMAINS_COUNT_FROM_PLACEMENT_PLUS_CTE = 10;

    /**
     * @var ResultsList
     */
    private $resultsList;
    /**
     * @var ApiInterface
     */
    private $apiClient;

    /**
     * ConfigController constructor.
     */
    public function __construct(Core $core, ApiInterface $apiClient)
    {
        parent::__construct($core);

        $this->apiClient = $apiClient;

        $this->resultsList = new ResultsList();
    }

    /**
     * @param $params
     * @return ResultsList
     * @throws Exception
     */
    public function get($params)
    {
        $args = [
            'name' => $params['searchTerm'],
            'limit' => self::SUGGESTION_DOMAIN_NAME_COUNT,
        ];

        $suggestionSettings = $params['suggestionSettings'];
        if (isset($suggestionSettings['preferredLanguage']) && !empty($suggestionSettings['preferredLanguage']))
            $args['language'] = $suggestionSettings['preferredLanguage'];

        $args['sensitive'] = isset($suggestionSettings['sensitive']) && $suggestionSettings['sensitive'] == 'on';

        if (isset($suggestionSettings['suggestTlds']) && count($suggestionSettings['suggestTlds']) > 0) {
            $args['tlds'] = array_map(function ($tld) {
                return mb_substr($tld, 1);
            }, explode(',', $suggestionSettings['suggestTlds']));
        }

        //get suggested domains
        try {
            $suggestedDomains = $this->apiClient->call('suggestNameDomainRequest', $args)->getData()['results'];
        } catch (Exception $e) {
            return $this->resultsList;
        }

        foreach ($suggestedDomains as $item) {
            $domain_sld = $item['domain'];
            $domain_tld = $item['tld'];
            $searchResult = new SearchResult($domain_sld, $domain_tld);

            if($params['OpenproviderPremium'] == true && isset($item['premium']) && $item['status'] == 'free') {
                $status = SearchResult::STATUS_NOT_REGISTERED;
                $searchResult->setPremiumDomain(true);

                $args['domain']['name']      = $domain_sld;
                $args['domain']['extension'] = $domain_tld;
                $args['operation']           = 'create';

                $createPricingResponse = $this->apiClient->call('retrievePriceDomainRequest', $args);
                if (!$createPricingResponse->isSuccess()) {
                    continue;
                }

                $createPricing = $createPricingResponse->getData();

                $args['operation'] = 'transfer';
                $transferPricingResponse  = $this->apiClient->call('retrievePriceDomainRequest', $args);
                if (!$transferPricingResponse->isSuccess()) {
                    continue;
                }

                $transferPricing = $transferPricingResponse->getData();

                // Retrieve the pricing
                $searchResult->setPremiumCostPricing(
                    array(
                        'register'  => $createPricing['price']['reseller']['price'],
                        'renew'     =>  $transferPricing['price']['reseller']['price'],
                        'CurrencyCode' => $createPricing['price']['reseller']['currency'],
                    )
                );
            } elseif($item['status'] == 'free') {
                $status = SearchResult::STATUS_NOT_REGISTERED;
            } else {
                $status = SearchResult::STATUS_REGISTERED;
            }

            $searchResult->setStatus($status);

            $this->resultsList->append($searchResult);
        }

        return $this->resultsList;
    }
}
