<?php

namespace OpenProvider\API;

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