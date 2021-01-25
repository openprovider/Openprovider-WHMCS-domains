<?php

namespace OpenProvider\API;
use VIISON\AddressSplitter\AddressSplitter;
/**
 * Class CustomerAddress
 * OpenProvider Registrar module
 *
 * @copyright Copyright (c) Openprovider 2018
 */
class CustomerAddress extends \OpenProvider\API\AutoloadConstructor
{
    public $street  =   null;
    public $number  =   null;
    public $suffix  =   null;
    public $zipcode =   null;
    public $city    =   null;
    public $state   =   null;
    public $country =   null;
    
    public function __construct($fields = array())
    {
        parent::__construct($fields);   

        if(isset($fields['fulladdress']))
        {
            $this->setAddress($fields['fulladdress']);
        }
    }
    
    /**
     * Format address
     * @param string
     */
    protected function setAddress($fullAddress)
    {
        try {
            $splitAddress = AddressSplitter::splitAddress($fullAddress);
            $housenumber = $splitAddress['houseNumberParts']['base'];
            $convertedAddress = $splitAddress['streetName'] . ' ' . $splitAddress['additionToAddress2'];

            $this->street   =   $convertedAddress;
            $this->number   =   $housenumber;
        } catch (\Exception $e)
        {
            if (strpos($e->getMessage(), ' could not be splitted into street name and house number.') !== false)
                $this->street = $fullAddress;
            else
                throw $e;
        }
    }
}
