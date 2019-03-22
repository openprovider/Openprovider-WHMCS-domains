<?php
namespace OpenProvider\WhmcsRegistrar\Library;

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
        // dd($additionalFields);
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

            // dd([$params,$additionalFields[$domainExtension]]);
            $this->ceAdditionalData->setTld($domain->extension);
            
            // Loop through every additional field to determine the location
            foreach($additionalFields[$domainExtension] as $field)
            {
                if($field['op_name'] == 'idnScript' && isset($ignoreIdnScript))
                    continue;
                    
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
                    $value = 1;

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