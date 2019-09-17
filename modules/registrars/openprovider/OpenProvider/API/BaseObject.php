<?php

namespace OpenProvider\API;

/**
 * Class BaseObject
 * OpenProvider Registrar module
 *
 * @copyright Copyright (c) Openprovider 2018
 */
class BaseObject
{
    public function toArray()
    {
        return json_decode(json_encode($object), true);
    }
}