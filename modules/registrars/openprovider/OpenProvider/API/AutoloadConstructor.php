<?php

namespace OpenProvider\API;

/**
 * Class AutoloadConstructor
 * OpenProvider Registrar module
 *
 * @copyright Copyright (c) Openprovider 2018
 */
class AutoloadConstructor
{
    public function __construct($fields = array())
    { 
        if($fields)
        {
            foreach($fields as $key => $val)
            {     
                if(property_exists($this, $key))
                {
                    $this->$key =   $val;
                }
            }
        }    
    }
}