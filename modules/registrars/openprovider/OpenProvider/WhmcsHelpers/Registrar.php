<?php
namespace OpenProvider\WhmcsHelpers;
use WHMCS\Database\Capsule;

/**
 * Manage the Registrar data
 *
 * @package default
 * @license  Licensed to OpenProvider by Yourwebhoster.eu
 **/
class Registrar
{
	/**
	 * Get the login 
	 *
	 * @return array ['registrar_parameters']
	 **/
	public static function get_login_data($registrar)
	{
		$registrar_data = Capsule::table('tblregistrars')
                ->where('registrar', $registrar)
                ->get();

        if(empty($registrar_data))
        	throw \Exception('Registrar not found');

        $return_data = [];

        foreach($registrar_data as $data)
        {
        	$return_data [ $data->setting ] = Crypt::decrypt($data->value);
        }

        return $return_data;
	}
} // END class Registrar