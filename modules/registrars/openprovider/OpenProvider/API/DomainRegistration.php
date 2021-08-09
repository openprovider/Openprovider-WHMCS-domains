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

    /**
     * @var array
     */
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
    public $nsTemplateName  =   null;
    
    /**
     *
     * @var int (0|1) default 0
     */
    public $useDomicile = false;

    /**
     *
     * @var int (0|1) default 0
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

    /**
     * @var boolean
     */
    public $isSectigoDnsEnabled;
}
