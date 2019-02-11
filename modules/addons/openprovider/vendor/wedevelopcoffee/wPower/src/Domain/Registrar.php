<?php
namespace WeDevelopCoffee\wPower\Domain;
use WHMCS\Database\Capsule;
use WeDevelopCoffee\wPower\Core\Crypt;
use WeDevelopCoffee\wPower\Models\DomainPricing;

/**
 * Manage the Registrar data
 *
 * @package default
 * @license  WeDevelop.coffee
 **/
class Registrar
{
    /**
     * Holds the registrar data.
     *
     * @var array
     */
    public static $data;

	/**
	 * Get the login 
	 *
	 * @return array ['registrar_parameters']
	 **/
	public static function getLoginData($registrar)
	{
		$registrar_raw_data = Capsule::table('tblregistrars')
                ->where('registrar', $registrar)
                ->get();

        if(empty($registrar_raw_data))
        	throw new \Exception('Registrar not found');

        $return_data = [];

        foreach($registrar_raw_data as $data)
        {
        	$return_data [ $data->setting ] = Crypt::decrypt($data->value);
        }

        self::$data = $return_data;

        return $return_data;
	}

    /**
     * Return the
     *
     * @param $key
     * @return mixed
     */
    public static function get($key)
    {
        if(empty(self::$data))
            self::get_login_data();

        return self::$data[$key];
    }
    
    /**
    * 
    * 
    * @return 
    */
    public function getTlds ($registrar)
    {
        $result = DomainPricing::where('autoreg', $registrar)->get();

        if(empty($result))
            return [];

        $tlds = [];

        foreach($result as $tld)
        {
            $tlds[$tld->extension] = $tld;
        }

        return $tlds;
    }
} // END class Registrar