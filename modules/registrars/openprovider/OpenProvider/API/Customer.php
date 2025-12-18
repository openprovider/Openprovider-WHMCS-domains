<?php
namespace OpenProvider\API;

use OpenProvider\WhmcsRegistrar\helpers\Dictionary;
use Illuminate\Database\Capsule\Manager as Capsule;
use WHMCS\Config\Setting;
use WHMCS\Language\ClientLanguage;

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
     * @var string
     */
    public $locale          =   'en_GB';

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
            if ($prefix == '')
                $prefix = 'owner';
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
                'language' => 'language'
            );

            foreach ($indexes as &$value)
            {
                $value = $prefix . $value;
            }


        }

        // Customer Name
        $initials = '';
        if (!empty($params[$indexes['firstname']])) {
            $initials .= mb_substr($params[$indexes['firstname']], 0, 1);
        }
        if (!empty($params[$indexes['lastname']])) {
            $initials .= '.' . mb_substr($params[$indexes['lastname']], 0, 1);
        }
        $name           =   new \OpenProvider\API\CustomerName(array(
            'initials'  =>  $initials ?: null,
            'firstName' =>  $params[$indexes['firstname']],
            'lastName'  =>  $params[$indexes['lastname']],
        ));

        //Customer Address
        $fullAddress = '';
        if ($getFromContactDetails) {
            if (isset($params[$indexes['address']]) && !empty($params[$indexes['address']])) {
                $fullAddress = $params[$indexes['address']];
            } else if (!empty(trim($params[$indexes['address1']] . ' ' . $params[$indexes['address2']]))) {
                $fullAddress = $params[$indexes['address1']] . ' ' . $params[$indexes['address2']];
            }
        } else {
            if (!empty(trim($params[$indexes['address1']] . ' ' . $params[$indexes['address2']]))) {
                $fullAddress = $params[$indexes['address1']] . ' ' . $params[$indexes['address2']];
            }
        }

        $address            =   new \OpenProvider\API\CustomerAddress(array(
            'fulladdress'   =>  $fullAddress ?: null,
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
        $this->locale       =   $this->getLocaleByLanguage($params[$indexes['language']] ?? null);;

        $this->additionalData = new CustomerAdditionalData();

        if (isset($params['additionalfields']) && !empty($params['additionalfields'])) {
            $additionalData = [];
            foreach($params['additionalfields'] as $additionalfield) {
                if (isset($additionalData['vat']) && empty($additionalData['vat'])) {
                    $additionalData['vat'] = $additionalfield;
                    $this->vat = $additionalfield;
                    continue;
                }

                if (isset($additionalData['socialSecurityNumber']) && empty($additionalData['socialSecurityNumber'])) {
                    $additionalData['socialSecurityNumber'] = $additionalfield;
                    $this->additionalData->set('socialSecurityNumber', $additionalfield);
                    $this->companyName = null;
                    continue;
                }

                $additionalData[$additionalfield] = '';
            }
        }

        if ($getFromContactDetails) {
            if (!empty($params['company name']) && !empty($params['company or individual id'])) {
                $this->additionalData->set('companyRegistrationNumber', $params['company or individual id']);
            }

            if (
                !empty($params['company name']) && !empty($params['vat or tax id'])
            ) {
                $this->vat = $params['vat or tax id'];
            }

            if (empty($params['company name']) && !empty($params['company or individual id'])) {
                $this->additionalData->set('passportNumber', $params['company or individual id']);
            }

            if (
                empty($params['company name']) && !empty($params['vat or tax id'])
            ) {
                $this->additionalData->set('socialSecurityNumber', $params['vat or tax id']);
            }
        }

        if (isset($_SESSION['contactsession']) && Capsule::schema()->hasTable('mod_contactsAdditional')) {
            $contactsNew = json_decode($_SESSION['contactsession'], true);
            if ($contactsNew[$prefix] != null && $contactsNew[$prefix][0] == 'c') {
                $contactid = substr($contactsNew[$prefix], 1);
                $idn = Capsule::table('mod_contactsAdditional')
                    ->where("contact_id", "=", $contactid)
                    ->first();
                $idn_id_type = $idn->identification_type;
                $idn_id = $idn->identification_number;
                $this->additionalData->set($idn_id_type, $idn_id);
            }
        }

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

    private function getLocaleByLanguage(?string $language)
    {
        // en_GB = "English (United Kingdom)" in the openprovider control panel. Templates should match this. (or be the default)
        $defaultLanguageCode = 'en_GB';

        if (!$language) {
            return $defaultLanguageCode;
        }

        // Map invalid WHMCS language codes to OpenProvider locale codes 
        $whmcsCorrectedMappingForOP = [
            'arabic' => 'ar_SA',
            'azerbaijani' => 'az_Latn_AZ',
            'norwegian' => 'nb_NO',
        ];

        if (isset($whmcsCorrectedMappingForOP[$language])) {
            return $whmcsCorrectedMappingForOP[$language];
        }

        return ClientLanguage::factory(Setting::getValue('Language'), $language)->toArray()['locale'] ?? $defaultLanguageCode;
    }
}
