<?php

namespace OpenProvider\API;

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