<?php
namespace OpenProvider\API;

/**
 * Class DomainRegistration
 * OpenProvider Registrar module
 *
 * @copyright Copyright (c) Openprovider 2018
 */

class DomainRegistration extends \OpenProvider\API\AutoloadConstructor
{
    /**
     *
     * @var \OpenProvider\API\Domain 
     */
    public $domain          =   null;
    
    /**
     *
     * @var int 
     */
    public $period          =   null;
    
    /**
     *
     * @var string
     */
    public $ownerHandle     =   null;
    
    /**
     *
     * @var string
     */
    public $adminHandle     =   null;
    
    /**
     *
     * @var string
     */
    public $techHandle      =   null;
    
    /**
     *
     * @var string
     */
    public $billingHandle   =   null;
    
    
    public $nameServers     =   null;
    
    /**
     *
     * @var string
     */
    public $autorenew       =   'off';
    
    /**
     *
     * @var \OpenProvider\API\AdditionalData
     */
    public $additionalData  =   null;
    
    /**
     *
     * @var string
     */
    public $nsTemplateName  =   '';
    
    /**
     *
     * @var bool (true|false) default false
     */
    public $useDomicile = false;

    /**
     *
     * @var bool (true|false) default false
     */
    public $isPrivateWhoisEnabled = false;

    /**
     * The cost for a premium domain.
     * @var float default 0
     */
    public $acceptPremiumFee = 0;

    /**
     * Enable or disable DNSsec
     *
     * @var boolean
     */
    public $isDnssecEnabled;
}
