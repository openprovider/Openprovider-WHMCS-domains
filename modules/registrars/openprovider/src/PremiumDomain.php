<?php
namespace OpenProvider\WhmcsRegistrar\src;

use Exception;
use OpenProvider\API\API;
use WHMCS\Domains\DomainLookup\ResultsList;

/**
 * Class PremiumDomain
 * @package OpenProvider\WhmcsRegistrar
 */
class PremiumDomain
{
    /**
     * @var API
     */
    private $API;

    /**
     * ConfigController constructor.
     */
    public function __construct(API $API)
    {
        $this->API = $API;
    }

    /**
     * Get the creation price of a premium domain.
     *
     * @param $params
     * @return array ['reseller', '
     * @throws Exception
     */
    public function getCreationPrice($domain_sld, $domain_tld)
    {
        $args['domain']['name'] = $domain_sld;
        $args['domain']['extension'] = $domain_tld;
        $args['operation'] = 'create';

        $create_pricing = $this->API->sendRequest('retrievePriceDomainRequest', $args);
        return $create_pricing['price'];
    }

    /**
     * Get the transfer price of a premium domain.
     * @param $params
     * @return ResultsList
     * @throws Exception
     */
    public function getTransferPrice($domain_sld, $domain_tld)
    {
        $args['domain']['name'] = $domain_sld;
        $args['domain']['extension'] = $domain_tld;
        $args['operation'] = 'transfer';

        $transfer_pricing = $this->API->sendRequest('retrievePriceDomainRequest', $args);
        return $transfer_pricing['price'];
    }

    /**
     * Get the registrar price when the reseller price matches.
     *
     * @param string $type
     * @param $domain_sld
     * @param $domain_tld
     * @param $price
     * @return float (0 when price does not match)
     * @throws Exception
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
}