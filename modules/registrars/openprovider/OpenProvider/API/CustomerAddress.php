<?php

namespace OpenProvider\API;

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
        $matches = array();
        if (preg_match('/^(\d+),?(.+)$/', $fullAddress, $matches))
        {
            $fullAddress = trim($matches[2] . ' ' . $matches[1]);
            // processing for US-styled addresses which start with the number
        }
        $tmp = explode(' ', $fullAddress);

        // Take care of nasty suffixes
        $tmpSuffix = end($tmp);
        $matches = array();
        if (preg_match('/^([\d]+)([^\d].*)$/', $tmpSuffix, $matches))
        {
            array_pop($tmp);
            $tmp[] = $matches[1];
            $tmp[] = trim($matches[2], " \t\n\r\0-");
        }

        $addressLength = count($tmp);
        $street = $tmp[0];
        $number = '';
        $suffix = '';
        $cnt = 1;

        while(($cnt < $addressLength) && !is_numeric($tmp[$cnt]))
        {
            $street .= ' ' . $tmp[$cnt];
            $cnt++;
        }
        if ($cnt < $addressLength)
        {
            $number = $tmp[$cnt];
            $cnt++;

            while($cnt < $addressLength)
            {
                $suffix .= $tmp[$cnt] . ' ';
                $cnt++;
            }
        }
        
        $this->street   =   $street;
        $this->number   =   $number;
        $this->suffix   =   $suffix;
    }
}
