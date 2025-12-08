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
    public function get (): array
    {   
        // Set the registrar
        $this->WAdditionalFields->setRegistrarName('openprovider');

        include($this->path->getModulePath().'/configuration/additionalfields.php');

        // Set the additional fields of OpenProvider
        $this->WAdditionalFields->setRegistrarAdditionalFields($additionaldomainfields);

        // Return the data.
        return $this->WAdditionalFields->registrarAdditionalFields;
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

            /**
             * op_name                  Fieldname with OP
             * op_explode               The explode delimiter.
             * op_location              Location where the data exists (customerExtensionAdditionalData, customerAdditionalData, domainAdditionalData or customer)
             * op_skip                  Do not send the parameter when the value is the same as op_skip.
             * op_dropdown_for_op_name  The value provided by the customer is used to define the op_name fieldname.
             */

            // Loop through all fields to find a decision master.
            foreach($additionalFields[$domainExtension] as $key => $field)
            {
                if(isset($field['op_dropdown_for_op_name']))
                {
                    // Set the name for the dynamic field.
                    $raw_options = explode(',', $field['Options']);
                    $dropdown_value = $params['additionalfields'][$field['Name']];
                    $valid_value = '';

                    // Since the input value is external input, we'll need to validate that the value
                    // really is configured in the field options.
                    foreach($raw_options as $raw_option)
                    {
                        $option = explode('|', $raw_option);

                        // Check if the provided value matches with the pre-configured allowed values.
                        if($option[0] == $dropdown_value)
                            $valid_value = $dropdown_value;
                    }

                    $dropdown_op_name_fields[$field['op_dropdown_for_op_name']] = $valid_value;
                }
            }

            // Loop through every additional field to determine the location
            foreach($additionalFields[$domainExtension] as $field)
            {
                // Do not run when the op_name is idnScript or when the field is a op_decision_master.
                if($field['op_name'] == 'idnScript' && isset($ignoreIdnScript)
                    || isset($field['op_decision_master']))
                    continue;

                // Check if a op_dropdown_for_op_name field is defined.
                if(isset($dropdown_op_name_fields[$field['op_name']]))
                    // There is. Change the op_name.
                    $field['op_name'] = $dropdown_op_name_fields[$field['op_name']];

                $value = $params['additionalfields'][$field['Name']];

                // Check if we have to run an explode.
                if(isset($field['Options']))
                {
                    if(isset($field['op_explode']) && strpos($value,'-'))
                        $value = explode($field['op_explode'], $value)[0];
                    elseif(isset($field['op_skip']) && $value == $field['op_skip'])
                        // Skip this value.
                        continue;
                        
                }
                elseif($field['Type']  == 'tickbox' && $value == 'on')
                    $value = "1";

                if($field['op_location'] == 'customerExtensionAdditionalData' && isset($params['additionalfields'][$field['Name']]))
                {
                    $name = $field['op_name'];
                    $this->ceAdditionalData->$name = $value;
                    $ceCustomerAdditionalData = true;
                } elseif($field['op_location'] == 'customerAdditionalData' && isset($params['additionalfields'][$field['Name']]))
                {
                    $name = $field['op_name'];
                    $this->customerAdditionalData[$name] = $value;
                    $customerAdditionalData = true;
                } elseif($field['op_location'] == 'domainAdditionalData' && isset($params['additionalfields'][$field['Name']]))
                {
                    $name = $field['op_name'];
                    $this->domainAdditionalData->$name = $value;
                } elseif($field['op_location'] == 'customer' && isset($params['additionalfields'][$field['Name']]))
                {
                    $name = $field['op_name'];
                    $this->customerData[$name] = $value;
                    $customerData = true;
                }
            }
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

}   
