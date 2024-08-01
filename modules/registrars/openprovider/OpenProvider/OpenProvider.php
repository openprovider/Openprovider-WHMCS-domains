<?php
namespace OpenProvider;

use WeDevelopCoffee\wPower\Models\Registrar;

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
	public $api;

	/**
	 * The openprovider domain object
	 *
	 * @var object
	 **/
	public $domain;


	/**
	 * The test mode
	 *
	 * @var boolean
	 **/
	public $isTestMode;

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
            $params = (new Registrar())->getRegistrarData()['openprovider'];

		$this->api      =   new \OpenProvider\API\API();
		$this->api->setParams($params);

		// Set the test mode
		if ($params['test_mode'] == 'on')
			$this->isTestMode = true;
		else
			$this->isTestMode = false;
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
    /**
     * Toggle Who is protection at OpenProvider
     *
     * @return array|string
     **/
    public function toggle_whois_protection($w_domain, \OpenProvider\API\Domain $domain, $opInfo)
    {
        // Check if we should auto renew or use the default settings
        // Note: the settings are in reverse since WHMCS updates the table after this operation.
        if($w_domain->idprotection == 1)
            $idprotection = 1; // OP sends the null value when no protection is set.
        else
            $idprotection = '0';

        // Check if openprovider has the same data
        if($opInfo['isPrivateWhoisEnabled'] != $idprotection)
        {
            if($idprotection == '0')
            {
                $opInfo['isPrivateWhoisEnabled'] = 1;
                $idprotection = '0';
            }

            $this->api->setPrivateWhoisEnabled($domain, $idprotection);

            return [ 'status'       => 'changed',
                'old_setting'   => $opInfo['isPrivateWhoisEnabled'],
                'new_setting'   => $idprotection];
        }

        return 'correct';
    }


	/**
	 * Get the domain info from OpenProvider mutation check XML API
	 * Return true if it is transferred domain. Else return false.
	 * @return boolean
	 * */
	public function check_transferred_status($domainId)
	{
		if ($domainId == null) {
			return false;
		}

		if ($this->isTestMode) {
			return false;
		}

		$result = $this->api->getLastMutation($domainId);
		if (isset($result['total']) && $result['total'] > 0) {
			if (isset($result['results'][0]['data']['action']) && $result['results'][0]['data']['action'] == 'outgoing_transfer') {
				return true;
			}
		}

		return false;
	}



} // END class OpenProvider
