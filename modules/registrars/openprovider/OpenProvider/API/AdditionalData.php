<?php

namespace OpenProvider\API;

class AdditionalData
{
    public $archiAcceptance             =   null;
    public $eligibilityTypeRelationship =   null;
    public $eligibilityType             =   null;
    public $bioAcceptance               =   null;
    public $legalType                   =   null;
    public $idnScript                   =   null;
    public $idNumber                    =   null;
    public $intendedUse                 =   null;
    public $passportNumber              =   null;
    public $socialSecurityNumber        =   null;
    public $companyRegistrationNumber   =   null;
    
    
    public function set($field, $value)
    {
        $t  =   array
        (
            'legaltype'                     =>  'legalType',
            'eligibilitytyperelationship'   =>  'eligibilityTypeRelationship',
            'eligibilitytype'               =>  'eligibilityType',
            'idnumber'                      =>  'idNumber',
            'idtype'                        =>  'idType',
            'bioacceptance'                 =>  'bioAcceptance',
            'archiacceptance'               =>  'archiAcceptance',
            'idnscript'                     =>  'idnScript',
            'intendedUse'                   =>  'intendedUse',
            'passportNumber'                =>  'passportNumber',
            'socialSecurityNumber'          =>  'socialSecurityNumber',
            'companyRegistrationNumber'     =>  'companyRegistrationNumber'
        );
        

        if(property_exists($this, $field))
        {
            $this->$field   =   $this->convert($field, $value);
            return true;
        }  
        
        $field  =   strtolower(str_replace(' ', '', $field)); 
        if(array_key_exists($field, $t))
        {
            $this->{$t[$field]} =   $this->convert($t[$field], $value);
            return true;
        }
        
        return false;
    }
    
    public function convert($key, $value)
    {
        switch($key)
        {
            case 'idNumber':
                return (int)$value;
                
            case 'bioAcceptance':
            case 'archiAcceptance':
                return $value == 'on' ? true : false;
                
            default:
                return $value;
        }
    }
}