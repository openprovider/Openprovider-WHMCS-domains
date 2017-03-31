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
    
}