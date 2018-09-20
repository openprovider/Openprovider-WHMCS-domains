<?php
namespace WeDevelopCoffee\wPower\Core;
use WHMCS\Database\Capsule;
/**
 * Helps to encrypt and decrypt WHMCS strings
 *
 * @package default
 * @license WeDevelop.coffee
 **/
class Crypt
{

	/**
	 * Decrypt $encrypted_string
	 *
	 * @param  string $encrypted_string The encrypted string
	 * @return string $decrypted_string
	 **/
	public static function decrypt($encrypted_string)
	{
		$command 	= 'DecryptPassword';
	    $postData 	= array(
	        'password2' => $encrypted_string,
	    );

	    $results = localAPI($command, $postData);

	    return html_entity_decode($results['password']);
	}

	/**
	 * Encrypt $decrypted_string
	 *
	 * @param  string $decrypted_string The decrypted string
	 * @return string $encrypted_string
	 **/
	public static function encrypt($decrypted_string)
	{
		$command 	= 'EncryptPassword';
	    $postData 	= array(
	        'password2' => $decrypted_string,
	    );

	    $results = localAPI($command, $postData);
	    
	    return $results['password'];
	}

} // END class Crypt