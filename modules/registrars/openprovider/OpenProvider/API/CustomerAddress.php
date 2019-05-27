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
        $splitAddress = AddressSplitter::splitAddress($fullAddress);
        $housenumber = $splitAddress['houseNumberParts']['base'] ;
        if(isset($splitAddress['additionToAddress2']) && $splitAddress['additionToAddress2'] != '')
            $housenumber .= ', ' . $splitAddress['additionToAddress2'];

        $convertedAddress = $splitAddress['streetName'];
        
        $this->street   =   $convertedAddress;
        $this->number   =   $housenumber;
    }
}
