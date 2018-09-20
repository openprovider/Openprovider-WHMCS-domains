<?php

namespace OpenProvider\API;

use JsonSerializable;

/**
 * Custom additional data
 * OpenProvider Registrar module
 *
 * @copyright Copyright (c) Openprovider 2018
 */
class AdditionalData implements JsonSerializable
{
    /**
     * The additional fields..
     *
     * @var array
     */
    protected $additionalFields = array();

    /**
     * Set the additional field.
     *
     * @param string $name
     * @param string $value
     * 
     * @return $this
     */
    public function __set($name, $value)
    {
        $this->additionalFields[$name] = $value;
        return $this;
    }

    /**
     * Get the additional field.
     *
     * @param string $name
     * 
     * @return mixed
     */
    public function __get($name)
    {
        if (array_key_exists($name, $this->data)) {
            return $this->additionalFields[$name];
        }

        return null;
    }

    /**
     * Check if additional field exists
     *
     * @param string $name
     * @return boolean
     */
    public function __isset($name)
    {
        return isset($this->additionalFields[$name]);
    }

    /**
     * Remove additional field
     *
     * @param string $name
     * 
     * @return $this
     */
    public function __unset($name)
    {
        unset($this->additionalFields[$name]);

        return $this;
    }

    /**
     * Make this usable for convertion to Json.
     *
     * @return void
     */
    public function jsonSerialize() {
        return $this->additionalFields;
    }

}