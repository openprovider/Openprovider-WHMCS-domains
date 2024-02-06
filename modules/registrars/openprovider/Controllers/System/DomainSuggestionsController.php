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

        if (!empty($suggestionSettings['suggestTlds'])) {
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

            $status = SearchResult::STATUS_NOT_REGISTERED;
            $searchResult->setStatus($status);

            if ($params['OpenproviderPremium'] == true && isset($item['premium'])) {
                $premiumPriceArgs = [];
                $searchResult->setPremiumDomain(true);

                $premiumPriceArgs['domainName']      = $domain_sld;
                $premiumPriceArgs['domainExtension'] = $domain_tld;
                $premiumPriceArgs['operation']       = 'create';

                $createPricingResponse = $this->apiClient->call('retrievePriceDomainRequest', $premiumPriceArgs);
                if (!$createPricingResponse->isSuccess()) {
                    continue;
                }

                $createPricing = $createPricingResponse->getData();

                // Retrieve the pricing
                $searchResult->setPremiumCostPricing(
                    array(
                        'register'  => $createPricing['price']['reseller']['price'],
                        'CurrencyCode' => $createPricing['price']['reseller']['currency'],
                    )
                );
            }

            $this->resultsList->append($searchResult);
        }

        return $this->resultsList;
    }
}
