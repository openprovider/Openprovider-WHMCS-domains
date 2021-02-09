<?php

namespace OpenProvider;

use OpenProvider\API\Domain;
use OpenProvider\API\JsonAPI;

/**
 * Helper to communicate with OpenProvider.
 * OpenProvider Registrar module
 *
 * @copyright Copyright (c) Openprovider 2018
 */
class OpenProvider
{
    /**
     * The api object
     *
     * @var object
     **/
    private $api;

    /**
     * The openprovider domain object
     *
     * @var object
     **/
    public $domain;

    /**
     * Launch the OpenProvider class.
     *
     * @param string $params *optional*. The registrar data.
     * @return void
     **@throws \Exception
     */
    public function __construct($params = null)
    {
        // Get the registrar setting
        $this->api = new JsonAPI($params);
    }

    /**
     * Set the domain sld and tld
     *
     * @param string $domain The domain including TLD
     * @return object $domain
     **/
    public function domain($domain)
    {
        $domain_sld = explode('.', $domain)[0];
        $domain_tld = substr(str_replace($domain_sld, '', $domain), 1);

        $this->domain = new Domain(array(
            'name'      => $domain_sld,
            'extension' => $domain_tld
        ));

        return $this->domain;
    }

    /**
     * @return object|JsonAPI
     */
    public function getApi()
    {
        return $this->api;
    }

    /**
     * Toggle autorenew at OpenProvider
     *
     * @return array|string
     **/
    public function toggle_autorenew($domain, $opInfo)
    {
        // Check if we should auto renew or use the default settings
        if ($domain->donotrenew == 0)
            $auto_renew = 'default';
        else
            $auto_renew = 'off';

        // Check if openprovider has the same data
        if ($opInfo['autorenew'] != $auto_renew) {
            $this->api->updateDomainAutorenewRequest($this->domain, $auto_renew);

            return ['status'      => 'changed',
                    'old_setting' => $opInfo['autorenew'],
                    'new_setting' => $auto_renew];
        }

        return 'correct';
    }

    /**
     * Toggle Who is protection at OpenProvider
     *
     * @return array|string
     **/
    public function toggle_whois_protection($w_domain, $opInfo)
    {
        // Check if we should auto renew or use the default settings
        // Note: the settings are in reverse since WHMCS updates the table after this operation.
        if ($w_domain->idprotection == 1)
            $idprotection = true;
        else
            $idprotection = false;

        // Prepare OP value
        if (empty($opInfo['is_private_whois_enabled']))
            $opInfo['is_private_whois_enabled'] = false;

        // Check if openprovider has the same data
        if ($opInfo['is_private_whois_enabled'] != $idprotection) {
            if ($idprotection == false) {
                $opInfo['is_private_whois_enabled'] = true;
                $idprotection                       = false;
            }

            $args = [
                'is_private_whois_enabled' => $idprotection,
            ];
            $this->api->updateDomainRequest($opInfo['id'], $args);

            return [
                'status'      => 'changed',
                'old_setting' => $opInfo['is_private_whois_enabled'],
                'new_setting' => $idprotection
            ];
        }

        return 'correct';
    }

} // END class OpenProvider