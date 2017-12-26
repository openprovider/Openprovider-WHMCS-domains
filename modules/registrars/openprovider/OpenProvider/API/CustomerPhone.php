<?php

namespace OpenProvider\API;

/**
 * Customer Phone Number
 */
class CustomerPhone extends \OpenProvider\API\AutoloadConstructor
{
    /**
     *
     * @var string 
     */
    public $countryCode         =   null;
    
    /**
     *
     * @var string 
     */
    public $areaCode            =   null;
    
    /**
     *
     * @var string 
     */
    public $subscriberNumber    =   null;
    
    
    public function __construct($fields = array())
    {
        parent::__construct($fields);
        
        if(isset($fields['phone country code']))
            $fields['fullphonenumber'] = '+' . $fields['phone country code'] . '.' . str_replace(' ', '', $fields['phone number']);

        if(isset($fields['phone number']) && !isset($fields['fullphonenumber']))
        {
            $fields['fullphonenumber'] = $this->generateFullPhoneNumber($fields['phone number'], $fields['country']);
        }

        if(isset($fields['fullphonenumber']))
        {
            $this->setPhoneNumber($fields['fullphonenumber']);
        }
    }
    
    /**
     * Set Phone Number
     * @param string
     */
    protected function setPhoneNumber($fullPhoneNumber)
    {
        $pos                    =   strpos($fullPhoneNumber, '.');
        $countryCode            =   substr($fullPhoneNumber, 0, $pos);
        $areaCodeLength         =   3;
        $areaCode               =   substr($fullPhoneNumber, $pos + 1, $areaCodeLength);
        $phoneNumber            =   substr($fullPhoneNumber, $pos + 1 + $areaCodeLength);
        
        $this->countryCode      =   $countryCode;
        $this->areaCode         =   $areaCode;
        $this->subscriberNumber =   $phoneNumber;
    }

    /**
     * Generate the phone number
     * 
     * @licensed to Openprovider
     */
    protected function generateFullPhoneNumber($phoneNumber, $country)
    {
        // Check if the input already has a full phone number.
        if(strpos($phoneNumber, '.'))
            return $phoneNumber;

        $countries = $this->getCountries();

        // Clean up the phone number
        if(substr($phoneNumber, 0, 1) === '0')
            $phoneNumber = substr($phoneNumber, 1);

        $fullPhoneNumber = '+' . $countries [$country]['callingCode'] . '.' . $phoneNumber;

        return $fullPhoneNumber;
    }

    /**
     * Get the countries from WHMCS.
     * 
     * @licensed to Openprovider
     */
    public function getCountries()
    {
        $countryResourcesPath = realpath(dirname( dirname( __DIR__ ) ) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR . 'country'  );
        
        $countries = json_decode(file_get_contents($countryResourcesPath . DIRECTORY_SEPARATOR . 'dist.countries.json'), true);

        // Check if the user has created custom countries
        $customCountriesPath = $countryResourcesPath . DIRECTORY_SEPARATOR . 'countries.json';
        if(file_exists($customCountriesPath))
        {
            $customCountries = json_decode(file_get_contents($countryResourcesPath . DIRECTORY_SEPARATOR . 'countries.json'), true);
            $countries = array_merge($countries,  $customCountries);
        }

        return $countries;
    }
    
}