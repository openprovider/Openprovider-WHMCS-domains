<?php

namespace OpenProvider\API;

use JsonSerializable;

/**
 * Custom additional data
 * OpenProvider Registrar module
 *
 * @copyright Copyright (c) Openprovider 2018
 */
class CustomerExtensionAdditionalData extends AdditionalData
{
    /**
     * The TLD of the domain
     *
     * @var string
     */
    protected $tld;

    /**
     * Set the TLD
     *
     * @param string $tld
     * @return $this
     */
    public function setTld ($tld)
    {
        $this->tld = $tld;
        return $this;
    }

    /**
     * Make this usable for convertion to Json.
     *
     * @return void
     */
    public function jsonSerialize() {
        return [
            'name' => $this->tld,
            'data' => $this->additionalFields
        ];
    }

}