<?php
namespace OpenProvider\WhmcsHelpers;
use WHMCS\Database\Capsule;
/**
 * Helps to encrypt and decrypt WHMCS strings
 *
 * @package default
 * @license  Licensed to OpenProvider by Yourwebhoster.eu
 **/
class Crypt
{
	private static $admin_user;

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

	    $admin_user = self::get_admin_user();

	    $results = localAPI($command, $postData, $admin_user);

	    return $results['password'];
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

	    $results = localAPI($command, $postData, null);
	    
	    return $results['password'];
	}

	/**
	 * Get the admin user
	 *
	 * @return string The $admin
	 **/
	private static function get_admin_user()
	{			
		if(self::$admin_user != '')
			return self::$admin_user;

		try {
			$admin_results = Capsule::table('tbladmins')
					->limit(1)
					->get();
		} catch (\Exception $e) {
			return null;
		}

		self::$admin_user = $admin_results[0]->username;
		return self::$admin_user;
	}
} // END class Crypt