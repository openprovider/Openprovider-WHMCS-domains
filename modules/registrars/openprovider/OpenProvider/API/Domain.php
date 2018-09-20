<?php

namespace OpenProvider\API;

/**
 * Class Domain
 * OpenProvider Registrar module
 *
 * @copyright Copyright (c) Openprovider 2018
 */

class Domain extends \OpenProvider\API\AutoloadConstructor
{
    /**
     *
     * @var string
     */
    public $name        =   null;
    
    /**
     *
     * @var string 
     */
    public $extension   =   null;
    
    public function getFullName()
    {
        return $this->name . '.' . $this->extension;
    }
}