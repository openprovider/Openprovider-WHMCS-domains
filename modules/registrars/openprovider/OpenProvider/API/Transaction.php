<?php

namespace OpenProvider\API;

/**
 * Class Transaction
 * OpenProvider Registrar module
 *
 * @copyright Copyright (c) Openprovider 2018
 */

class Transaction extends \OpenProvider\API\AutoloadConstructor
{
    /**
     *
     * @var string
     */
    public $limit        =   100;
    
    /**
     *
     * @var string 
     */
    public $offset   =   0;

    /**
     *
     * @var string 
     */
    public $startCreationDate   =   null;

    /**
     *
     * @var string 
     */
    public $endCreationDate   =   null;

    /**
     *
     * @var string 
     */
    public $subject   =   null;
}