<?php

namespace WeDevelopCoffee\wPower\Domain;

use WeDevelopCoffee\wPower\Core\Path;
use WeDevelopCoffee\wPower\Models\Registrar;

/**
 * Replace the additional fields.
 *
 * @package default
 * @license  WeDevelop.coffee
 **/
class AdditionalFields
{
    /**
     * Path object
     *
     * @var object
     */
    protected $path;
    /**
     * The registrar
     *
     * @var string
     */
    protected $registrar;
    /**
     * The TLDs configured in WHMCS to be used with this registrar
     *
     * @var array
     */
    protected $registrarTlds;
    /**
     * Additional fields as provided by WHMCS.
     *
     * @var array
     */
    protected $distAdditionalFields;
    /**
     * Additional fields as provided by the registrar.
     *
     * @var array
     */
    protected $additionalFields;
    /**
     * Constructor
     *
     * @return
     */
    public function __construct(Path $path, Registrar $registrar)
    {
        $this->path = $path;
        $this->registrar = $registrar;
    }

    /**
     * Get the additional fields of the distribution
     *
     * @return void
     */
    public function getDistAdditionalFields()
    {
        include($this->path->getDocRoot() . '/resources/domains/dist.additionalfields.php');
        $this->distAdditionalFields = $additionaldomainfields;
        return $additionaldomainfields;
    }
    /**
     * Get the final filtered list of additional fields.
     *
     * @return array
     */
    public function getFilteredAdditionalFields()
    {
        // Get the distribution fields.
        $this->getDistAdditionalFields();
        // Loop through the fields and compile a finished additional fields set.
        $additionalField = [];
        foreach ($this->registrarAdditionalFields as $tld => $fields) {
            $tmpRegistrarFields = [];
            // Only override TLDs that belong to this registrar.
            if (isset($this->registrarTlds[$tld])) {
                // Set the additional fields.
                $additionalFields[$tld] = $fields;
                // Temporarily store the field names to prevent that they will get removed from WHMCS.
                foreach ($fields as $field) {
                    $tmpRegistrarFields[$field['Name']] = true;
                }
                // Remove the distributed fields.
                if (isset($this->distAdditionalFields[$tld])) {
                    // Disable the additional fields.
                    foreach ($this->distAdditionalFields[$tld] as $whmcsField) {
                        if (!is_array($whmcsField)) {
                            $name = $whmcsField;
                            $whmcsField = array();
                            $whmcsField['Name'] = $name;
                        }
                        // Only remove fields that are not in the Registrars field.
                        if (!isset($tmpRegistrarFields[$whmcsField['Name']])) {
                            $removeField = [];
                            $removeField['Name']    = $whmcsField['Name'];
                            $removeField['Remove']  = true;
                            $additionalFields[$tld][] = $removeField;
                        }
                    }
                }
            }
            // Clean up for the next round.
            unset($tmpRegistrarFields);
        }
        return $additionalFields;
    }
    /**
     * Set the additional fields provided by the provider.
     *
     * @return $this
     */
    public function setRegistrarAdditionalFields($additionalFields)
    {
        $this->registrarAdditionalFields = $additionalFields;

        return $this;
    }
    /**
     * Replace the distribution additional fields with the registrar's
     * additional fields.
     *
     * @return $this
     */
    public function setRegistrarName($registrar)
    {
        $this->registrarName    = $registrar;
        $this->registrarTlds    = $this->registrar->getTlds($registrar);
        return $this;
    }
}
