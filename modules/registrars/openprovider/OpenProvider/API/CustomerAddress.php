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

        if (isset($fields['country']) && !is_null($fields['country'])) {
            $this->country = strtoupper(trim($fields['country']));
        }

        if (isset($fields['fulladdress']) && !is_null($fields['fulladdress'])) {
            $this->setAddress($fields['fulladdress']);
        }
    }
    
    /**
     * Format address
     * @param string
     */
    protected function setAddress($fullAddress)
    {
        $country = $this->country ?? '';

        try {
            $splitAddress = AddressSplitter::splitAddress($fullAddress, $country);
            $housenumber = $splitAddress['houseNumberParts']['base'];
          
            if ($country == 'US') {
                $convertedAddress = trim($splitAddress['houseNumber'] . ' ' . $splitAddress['streetName'] . ' ' . $splitAddress['additionToAddress2']);
                $this->street   =   $convertedAddress;

            } else {
                $convertedAddress = $splitAddress['streetName'] . ' ' . $splitAddress['additionToAddress2'];
                $this->street   =   $convertedAddress;
                $this->number   =   $housenumber;
            }

        } catch (\Exception $e)
        {
            if (strpos($e->getMessage(), ' could not be splitted into street name and house number.') !== false)
                $this->street = $fullAddress;
            else
                throw $e;
        }
    }
}
