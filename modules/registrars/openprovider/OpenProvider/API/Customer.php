<?php
namespace OpenProvider\API;
use OpenProvider\WhmcsHelpers\CustomField;
use OpenProvider\WhmcsRegistrar\helpers\Dictionary;
use WeDevelopCoffee\wPower\Domain\AdditionalFields;

/**
 * Customer
 * OpenProvider Registrar module
 *
 * @copyright Copyright (c) Openprovider 2018
 */

class Customer
{
    /**
     *
     * @var string
     */
    public $companyName     =   null;

    /**
     *
     * @var string
     */
    public $vat             =   null;

    /**
     *
     * @var \OpenProvider\API\CustomerName
     */
    public $name            =   null;

    /**
     *
     * @var type
     */
    public $gender          =   null;

    /**
     *
     * @var \OpenProvider\API\CustomerAddress
     */
    public $address         =   null;

    /**
     *
     * @var \OpenProvider\API\CustomerPhone
     */
    public $phone           =   null;

    /**
     *
     * @var string
     */
    public $email           =   null;

    /**
     *
     * @var string
     */
    public $handle          =   null;

    /**
     *
     * @var \OpenProvider\API\CustomerAdditionalData
     */
    public $additionalData  =   null;

    /**
     *
     * @var \OpenProvider\API\CustomerExtensionAdditionalData[]
     */
    public $extensionAdditionalData  =   null;

    /**
     * @var \OpenProvider\API\CustomerTags
     */
    public $tags = null;

    /**
     *
     * @param array $params
     * @param string $prefix
     */
    public function __construct($params, $prefix = '')
    {
        if($prefix == 'all')
            $prefix = '';
            
        if($prefix == 'registrant')
            $prefix = 'owner';
        
        $getFromContactDetails = false;
        if (isset($params['contactdetails']))
        {
            $getFromContactDetails = true;
        }

        if ($getFromContactDetails == true)
        {
            $indexes = array(
                'firstname' => 'first name',
                'lastname' => 'last name',
                'gender' => 'gender',
                'address' => 'address',
                'postcode' => 'zip code',
                'city' => 'city',
                'state' => 'fullstate',
                'country' => 'country',
                'phone number' => 'phone number',
                'phone country code' => 'phone country code',
                'email' => 'email address',
                'companyname' => 'company name',
            );

            if(!isset($params["contactdetails"][$prefix]['fullstate']))
                $indexes['state'] = 'state';

            $params = array_change_key_case($params["contactdetails"][ucfirst($prefix)]);
        }
        else
        {
            $indexes = array(
                'firstname' => 'firstname',
                'lastname' => 'lastname',
                'gender' => 'gender',
                'address1' => 'address1',
                'address2' => 'address2',
                'postcode' => 'postcode',
                'city' => 'city',
                'state' => 'fullstate',
                'country' => 'country',
                'fullphonenumber' => 'fullphonenumber',
                'phone country code' => 'Phone Country Code',
                'email' => 'email',
                'companyname' => 'companyname',
            );

            foreach ($indexes as &$value)
            {
                $value = $prefix . $value;
            }
        }

        // Customer Name
        $initials       =   mb_substr($params[$indexes['firstname']], 0, 1) . '.' . mb_substr($params[$indexes['lastname']], 0, 1);
        $name           =   new \OpenProvider\API\CustomerName(array(
            'initials'  =>  $initials,
            'firstName' =>  $params[$indexes['firstname']],
            'lastName'  =>  $params[$indexes['lastname']],
        ));

        //Customer Address
        $address            =   new \OpenProvider\API\CustomerAddress(array(
            'fulladdress'   =>  $getFromContactDetails ? $params[$indexes['address']] : $params[$indexes['address1']] . ' ' . $params[$indexes['address2']],
            'zipcode'       =>  $params[$indexes['postcode']],
            'city'          =>  $params[$indexes['city']],
            'state'         =>  $params[$indexes['state']],
            'country'       =>  $params[$indexes['country']],
        ));

        //Phone number
        if(!isset($params[$indexes['fullphonenumber']]))
        {
            $phoneParams = array(
                'phone number'   =>  $params[$indexes['phone number']],
                'country'        =>  $params[$indexes['country']],
            );

            // Check if there is a country code included.
            if(isset($params[$indexes['phone country code']]) && $params[$indexes['phone country code']] != '')
                $phoneParams['phone country code'] = $params[$indexes['phone country code']];

            $phone              =   new \OpenProvider\API\CustomerPhone($phoneParams);
        }
        else
        {
            $phone              =   new \OpenProvider\API\CustomerPhone(array(
                'fullphonenumber'   =>  $params[$indexes['fullphonenumber']]
            ));
        }

        // Tags
        if (isset($params['tags']))
            $tags = new \OpenProvider\API\CustomerTags($params['tags']);
        else
            $tags = new \OpenProvider\API\CustomerTags('');

        // set values
        $this->name         =   $name;
        $this->gender       =   isset($params[$indexes['gender']]) ? $params[$indexes['gender']] : \OpenProvider\API\APIConfig::$defaultGender;
        $this->address      =   $address;
        $this->phone        =   $phone;
        $this->email        =   $params[$indexes['email']];
        $this->companyName  =   $params[$indexes['companyname']];
        $this->tags         =   $tags->getTags();
//        $this->vat          =   CustomField::getValueFromCustomFields('VATNumber', $params['customfields']);;

        $this->additionalData = new CustomerAdditionalData();
    }

    public function setAddressStateShort()
    {
        switch ($this->address->country) {
            case 'US':
                $USStates = Dictionary::get(Dictionary::USStates);
                $state = ucwords(strtolower($this->address->state));
                if ($this->address->state && isset($USStates[$state]))
                    $this->address->state = $USStates[$state];
                break;

            default:
                break;
        }
    }

    public function setTags($tags)
    {
        $this->tags = (new CustomerTags($tags))->getTags();
    }
}
