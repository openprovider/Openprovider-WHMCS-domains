<?php
namespace OpenProvider\API;

/**
 * Customer
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
     * @param type $params
     * @param string $prefix
     */
    public function __construct($params, $prefix = '')
    {
        $getFromContactDetails = false;
        if (isset($params['getFromContactDetails']) && $params['getFromContactDetails'])
        {
            $getFromContactDetails = true;
        }
        
        if ($getFromContactDetails)
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
                'fullphonenumber' => 'phone number',
                'email' => 'email address',
                'companyname' => 'company name',
            );
            
            $params = array_change_key_case($params["contactdetails"][$prefix]);
            
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
                'email' => 'email',
                'companyname' => 'companyname',
            );

            foreach ($indexes as &$value)
            {
                $value = $prefix . $value;
            }
        }

        // Customer Name
        $initials       =   substr($params[$indexes['firstname']], 0, 1) . '.' . substr($params[$indexes['lastname']], 0, 1);
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
        $phone              =   new \OpenProvider\API\CustomerPhone(array(
            'fullphonenumber'   =>  $params[$indexes['fullphonenumber']]
        ));
        
        // set values
        $this->name         =   $name;
        $this->gender       =   isset($params[$indexes['gender']]) ? $params[$indexes['gender']] : \OpenProvider\API\APIConfig::$defaultGender;
        $this->address      =   $address;
        $this->phone        =   $phone;
        $this->email        =   $params[$indexes['email']];
        $this->companyName  =   $params[$indexes['companyname']];

    }
}
