<?php
namespace OpenProvider;
use OpenProvider\WhmcsHelpers\Registrar;

/**
 * Helper to communicate with OpenProvider.
 *
 * @package default
 * @license  Licensed to OpenProvider by Yourwebhoster.eu
 **/
class OpenProvider
{
	/**
	 * The api object
	 *
	 * @var object
	 **/
	public $api;

	/**
	 * The openprovider domain object
	 *
	 * @var object
	 **/
	public $domain;

	/**
	 * Launch the OpenProvider class.
	 *
	 * @param  string $params *optional*. The registrar data.
	 * @return void
	 **/
	public function __construct($params = null)
	{
		// Get the registrar setting
    	if($params == null)
    		$params 		= 	Registrar::get_login_data('openprovider');

		$this->api      =   new \OpenProvider\API\API($params);
	}

	/**
	 * Set the domain sld and tld
	 *
	 * @param  string $domain The domain including TLD
	 * @return object $domain
	 **/
	public function domain($domain)
	{
		$domain_sld = explode('.', $domain)[0];
        $domain_tld = substr(str_replace($domain_sld, '', $domain), 1);

		$this->domain             =   new \OpenProvider\API\Domain(array(
            'name'          =>  $domain_sld,
            'extension'     =>  $domain_tld
        ));

        return $this->domain;
	}

	/**
	 * Toggle autorenew at OpenProvider
	 *
	 * @return array|string
	 **/
	public function toggle_autorenew($domain, $opInfo)
	{
		// Check if we should auto renew or use the default settings
	    if($domain->donotrenew == 0)
	        $auto_renew = 'default';
	    else
	        $auto_renew = 'off';

	    // Check if openprovider has the same data
	    if($opInfo['autorenew'] != $auto_renew)
	    {
	    	$this->api->setAutoRenew($this->domain, $auto_renew);

	    	return [ 'status'       => 'changed',
                    'old_setting'   => $opInfo['autorenew'],
                    'new_setting'   => $auto_renew];
	    }

	    return 'correct';
	}

	/**
	 * Toggle Who is protection at OpenProvider
	 *
	 * @return array|string
	 **/
	public function toggle_whois_protection($domain, $opInfo)
	{
		// Check if we should auto renew or use the default settings
	    if($domain->idprotection == 0)
            $idprotection = null; // OP sends the null value when no protection is set.
	    else
            $idprotection = '1';

	    // Check if openprovider has the same data
	    if($opInfo['isPrivateWhoisEnabled'] != $idprotection)
	    {
	        if($idprotection == null)
	        {
                $opInfo['isPrivateWhoisEnabled'] = 1;
                $idprotection = '0';
            }

	    	$this->api->setPrivateWhoisEnabled($this->domain, $idprotection);

	    	return [ 'status'       => 'changed',
                    'old_setting'   => $opInfo['isPrivateWhoisEnabled'],
                    'new_setting'   => $idprotection];
	    }

	    return 'correct';
	}

} // END class OpenProvider