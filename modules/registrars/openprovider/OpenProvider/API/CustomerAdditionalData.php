<?php

namespace OpenProvider\API;
/**
 * Custom additional data
 */
class CustomerAdditionalData extends \OpenProvider\API\AutoloadConstructor
{
    public $socialSecurityNumber                =   null;
    public $passportNumber                      =   null;
    public $birthDate                           =   null;
    public $birthAddress                        =   null;
    public $birthZipcode                        =   null;
    public $birthCity                           =   null;
    public $birthState                          =   null;
    public $birthCountry                        =   null;
    public $companyRegistrationCity             =   null;
    public $companyRegistrationNumber           =   null;
    public $companyRegistrationSubscriptionDate =   null;
    public $headquartersAddress                 =   null;
    public $headquartersZipcode                 =   null;
    public $headquartersCity                    =   null;
    public $headquartersState                   =   null;
    public $headquartersCountry                 =   null;
    public $nexusCategory                       =   null;
    public $nexusValidator                      =   null;
    public $uin                                 =   null;
    
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
            'intendedUse'                   =>  'Intended use',
            'passportNumber'                =>  'passportNumber',
            'socialSecurityNumber'          =>  'socialSecurityNumber',
            'companyRegistrationNumber'     =>  'companyRegistrationNumber',
            'vat'                           =>  'vat'
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