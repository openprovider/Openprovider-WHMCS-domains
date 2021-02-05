<?php

namespace OpenProvider\WhmcsRegistrar\Models;

use OpenProvider\WhmcsRegistrar\src\Configuration;
use WeDevelopCoffee\wPower\Models\Registrar;

/**
 * Class Domain
 * @package OpenProvider
 */
class Domain extends \WeDevelopCoffee\wPower\Models\Domain
{

    /**
     * Check if the domain should be renewed.
     *
     * @param $domain
     */
    public function check_renew_domain_setting_upon_completed_transfer()
    {
        $setting_value = Configuration::getOrDefault('renewTldsUponTransferCompletion', '');

        // When nothing was found; return false.
        if(count($setting_value) == 0
            || count($setting_value ) && $setting_value == '')
            return false;

        $tlds = explode(",",$setting_value);

        // We found it!
        $tld = $this->split_domain($this->domain)['tld'];

        if(in_array($tld, $tlds))
            return true;

        // The domain TLD does not match with the renewal TLDs.
        return false;
    }

    /**
     * Split a domain into a SLD and TLD.
     *
     * @param $domain
     * @return mixed
     */
    public function split_domain($domain)
    {
        $explode_domain = explode('.', $domain);

        $split_domain ['sld'] = $explode_domain[0];
        $split_domain ['tld'] = str_replace($split_domain['sld'] . '.', '', $domain);

        return $split_domain;
    }
}