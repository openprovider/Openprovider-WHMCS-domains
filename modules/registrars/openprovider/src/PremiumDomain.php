<?php

namespace OpenProvider\WhmcsRegistrar\src;

use OpenProvider\API\ApiInterface;
use WHMCS\Domains\DomainLookup\ResultsList;

/**
 * Class PremiumDomain
 * @package OpenProvider\WhmcsRegistrar
 */
class PremiumDomain
{
    /**
     * @var ApiInterface
     */
    private $apiClient;

    /**
     * Get the creation price of a premium domain.
     *
     * @param $domain_sld
     * @param $domain_tld
     * @return array
     */
    public function getCreationPrice($domain_sld, $domain_tld)
    {
        $args = [
            'domainName' => $domain_sld,
            'domainExtension' => $domain_tld,
            'operation' => 'create',
        ];

        $create_pricing = $this->apiClient->call('retrievePriceDomainRequest', $args)->getData();
        return $create_pricing['price'];
    }

    /**
     * Get the transfer price of a premium domain.
     * @param $domain_sld
     * @param $domain_tld
     * @return ResultsList
     */
    public function getTransferPrice($domain_sld, $domain_tld)
    {
        $args = [
            'domainName' => $domain_sld,
            'domainExtension' => $domain_tld,
            'operation' => 'transfer',
        ];

        $transfer_pricing = $this->apiClient->call('retrievePriceDomainRequest', $args)->getData();
        return $transfer_pricing['price'];
    }

    /**
     * Get the registrar price when the reseller price matches.
     *
     * @param string $type
     * @param $domain_sld
     * @param $domain_tld
     * @param $resellerPrice
     * @return float (0 when price does not match)
     */
    public function getRegistrarPriceWhenResellerPriceMatches($type = 'create', $domain_sld, $domain_tld, $resellerPrice)
    {
        if($type == 'create')
            $price = $this->getCreationPrice($domain_sld, $domain_tld);
        else
            $price = $this->getTransferPrice($domain_sld, $domain_tld);

        if($price['reseller']['price'] == $resellerPrice)
            return $price['product']['price'];
        else
            return 0;
    }

    /**
     * @param ApiInterface $apiClient
     * @return void
     */
    public function setApiClient(ApiInterface $apiClient)
    {
        $this->apiClient = $apiClient;
    }
}
