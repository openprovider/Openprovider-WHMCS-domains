<?php

namespace WHMCS\Domains\DomainLookup;

/**
 * Class SearchResult
 * @package WHMCS\Domains\DomainLookup
 */
class SearchResult
{
    const STATUS_NOT_REGISTERED=1;
    const STATUS_REGISTERED=2;
    const STATUS_RESERVED=3;

    public $status;
    public $domain_sld;
    public $domain_tld;
    public $premiumDomain;
    public $premiumCostPricing;

    public function __constrct($domain_sld, $domain_tld)
    {
        $this->domain_sld = $domain_sld;
        $this->domain_tld = $domain_sld;
    }

    public function setStatus($status)
    {
        $this->status = $status;
    }

    public function setPremiumDomain($value)
    {
        $this->premiumDomain = $value;
    }

    public function setPremiumCostPricing($pricing)
    {
        $this->premiumCostPricing = $pricing;
    }

}