<?php
namespace OpenProvider\WhmcsRegistrar\src;

use idna_convert;
use OpenProvider\API\Domain;
use OpenProvider\API\AdditionalData;
use WeDevelopCoffee\wPower\Core\Path;
use OpenProvider\API\CustomerExtensionAdditionalData;
use WeDevelopCoffee\wPower\Domain\AdditionalFields as WAdditionalFields;

/**
 * Helper to hook additional fields into WHMCS.
 * OpenProvider Registrar module
 *
 * @copyright Copyright (c) Openprovider 2018
 */
class AdditionalFields
{

    /**
     * The additional fields class.
     *
     * @var object
     */
    protected $WAdditionalFields;

    /**
     * The additional fields class.
     *
     * @var object
     */
    protected $path;

    /**
     * The additional data for a domain
     *
     * @var object OpenProvider\API\AdditionalData
     */
    protected $domainAdditionalData;

    /**
     * Customer data.
     *
     * @var array [ $key => $value ]
     */
    protected $customerData;

    /**
     * Customer data.
     *
     * @var array [ $key => $value ]
     */
    protected $customerAdditionalData;

    /**
     * The additional fields object
     *
     * @var object OpenProvider\API\CustomerExtensionAdditionalData
     */
    protected $ceAdditionalData;

    /**
    * Start the additional fields class.
    * 
    * @return 
            $ceAdditionalData = new ();
    */
    public function __construct (WAdditionalFields $WAdditionalFields, Path $path, AdditionalData $domainAdditionalData, CustomerExtensionAdditionalData $ceAdditionalData)
    {
        $this->WAdditionalFields    = $WAdditionalFields;
        $this->path                 = $path;
        $this->domainAdditionalData = $domainAdditionalData;
        $this->ceAdditionalData     = $ceAdditionalData;
    }

    
    /**
    * Replace the additional fields with the fields of OpenProvider.
    * 
    * @return array $fields
    */
    public function get ()
    {   
        // Set the registrar
        $this->WAdditionalFields->setRegistrarName('openprovider');

        include($this->path->getModulePath().'/configuration/additionalfields.php');

        // Set the additional fields of OpenProvider
        $this->WAdditionalFields->setRegistrarAdditionalFields($additionaldomainfields);

        // Return the data.
        return $this->WAdditionalFields->getFilteredAdditionalFields();
    }

    /**
     * Returns all extra fields per additional field type.
     *
     * @param array $params Passthrough from WHMCS
     * @param object $domain \OpenProvider\API\Domain
     * @return array
     */
    public function processAdditionalFields($params, Domain $domain)
    {
        // Get the additional fields of OpenProvider.
        $additionalFields = $this->get();

        $domainExtension = '.'.$domain->extension;

        // Prepare return data
        $foundAdditionalFields = [];

        // Check if there are any additional fields.
        if(isset($additionalFields[$domainExtension]))
        {
            // Ignore idn script or not.
            $idn = new idna_convert();
            if($params['sld'].'.'.$params['tld'] == $idn->encode($params['sld'].'.'.$params['tld']) 
                && strpos($params['sld'].'.'.$params['tld'], 'xn--') === false)
            {
                $ignoreIdnScript = true;
            }

            $this->ceAdditionalData->setTld($domain->extension);

            // Set parant array
            $op_parent = [];

            // Detect the parents and get the values.
            list($op_parent, $additionalFields) = $this->detectedParents($params,
                $additionalFields, $domainExtension, $ignoreIdnScript, $op_parent);

            // Process the childs and set the additional fields.
            list($params, $additionalFields) = $this->processChildren($params, $additionalFields,
                $domainExtension, $ignoreIdnScript, $op_parent);

            // Process values, including childs and normal ones.
            list($ceCustomerAdditionalData, $customerAdditionalData, $customerData) = $this->processValues($params,
                $additionalFields, $domainExtension, $ignoreIdnScript);
        }

        if(isset($ceCustomerAdditionalData))
            $foundAdditionalFields['extensionCustomerAdditionalData']   = [$this->ceAdditionalData];

        if(isset($customerAdditionalData))
            $foundAdditionalFields['customerAdditionalData']   = $this->customerAdditionalData;

        if(isset($customerData))
            $foundAdditionalFields['customer']   = $this->customerData;

        $foundAdditionalFields['domainAdditionalData']              = $this->domainAdditionalData;

        return $foundAdditionalFields;
    }

    /**
     * @param $params
     * @param $field
     * @return array
     */
    public function detectValue($params, $field): array
    {
        $value = $params['additionalfields'][$field['Name']];

        // Check if we have to run an explode.
        if (isset($field['Options'])) {
            if (isset($field['op_explode']) && strpos($value, '-')) {
                $value = explode($field['op_explode'], $value)[0];
            }
            elseif(isset($field['op_skip']) && $value == $field['op_skip'])
//             Skip this value.
                return [['continue' => true], $field];

        } elseif ($field['Type'] == 'tickbox' && $value == 'on') {
            $value = 1;
        }
        return array ($value, $field);
    }

    /**
     * Loop through the additional fields and find the parents.
     *
     * @param $params
     * @param array $additionalFields
     * @param string $domainExtension
     * @param bool $ignoreIdnScript
     * @param array $op_parent
     * @return array
     */
    public function detectedParents(
        $params,
        array $additionalFields,
        string $domainExtension,
        bool $ignoreIdnScript,
        array $op_parent
    ): array {
        foreach ($additionalFields[$domainExtension] as $key => $field) {
            if (
                ($field['op_name'] == 'idnScript' && isset($ignoreIdnScript))
                || !isset($field['op_is_parent'])
            ) {
                continue;
            }

            list($value, $field) = $this->detectValue($params, $field);
            if (isset($value['continue'])) {
                continue;
            }

            $field ['value'] = $value;

            $op_parent [$field['op_name']] = $field;

            // Parent should not get processed.
            unset($additionalFields[$domainExtension][$key]);
        }
        return array ($op_parent, $additionalFields);
    }

    /**
     * Loop through the additional data and process childs.
     *
     * @param $params
     * @param $additionalFields
     * @param string $domainExtension
     * @param bool $ignoreIdnScript
     * @param $op_parent
     * @return array
     */
    public function processChildren(
        $params,
        $additionalFields,
        string $domainExtension,
        bool $ignoreIdnScript,
        $op_parent
    ): array {
        foreach ($additionalFields[$domainExtension] as $key => $field) {
            if (
                ($field['op_name'] == 'idnScript' && isset($ignoreIdnScript))
                || !isset($field['op_has_parent'])
            ) {
                continue;
            }

            list($value, $field) = $this->detectValue($params, $field);
            if (isset($value['continue'])) {
                continue;
            }

            if (isset($op_parent [$field['op_has_parent']])) {
                $parent = $op_parent [$field['op_has_parent']];

                $tmpField = [
                    'Name' => $parent['value'], // The value of the parent is the field name in OpenProvider.
                    'op_name' => $parent['value'],
                    'op_location' => $parent['op_location'],
                ];
                $additionalFields[$domainExtension][] = $tmpField;

                $params['additionalfields'][$tmpField['Name']] = $value;

                // Child should not get processed.
                unset($additionalFields[$domainExtension][$key]);
                unset($params['additionalfields'][$field['Name']]);
            }
        }
        return [$params, $additionalFields];
    }

    /**
     * @param $params
     * @param $additionalFields
     * @param string $domainExtension
     * @param bool $ignoreIdnScript
     * @return array
     */
    public function processValues($params, $additionalFields, string $domainExtension, bool $ignoreIdnScript): array
    {
        foreach ($additionalFields[$domainExtension] as $field) {
            if ($field['op_name'] == 'idnScript' && isset($ignoreIdnScript)) {
                continue;
            }

            list($value, $field) = $this->detectValue($params, $field);

            if (isset($value['continue'])) {
                continue;
            }

            if ($field['op_location'] == 'customerExtensionAdditionalData' && isset($params['additionalfields'][$field['Name']])) {
                $name = $field['op_name'];
                $this->ceAdditionalData->$name = $value;
                $ceCustomerAdditionalData = true;
            } elseif ($field['op_location'] == 'customerAdditionalData' && isset($params['additionalfields'][$field['Name']])) {
                $name = $field['op_name'];
                $this->customerAdditionalData[$name] = $value;
                $customerAdditionalData = true;
            } elseif ($field['op_location'] == 'domainAdditionalData' && isset($params['additionalfields'][$field['Name']])) {
                $name = $field['op_name'];
                $this->domainAdditionalData->$name = $value;
            } elseif ($field['op_location'] == 'customer' && isset($params['additionalfields'][$field['Name']])) {
                $name = $field['op_name'];
                $this->customerData[$name] = $value;
                $customerData = true;
            }
        }
        return array ($ceCustomerAdditionalData, $customerAdditionalData, $customerData);
    }

}   