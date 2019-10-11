<?php

namespace OpenProvider\WhmcsRegistrar\Controllers\System;

use OpenProvider\API\API;
use OpenProvider\API\Domain;
use OpenProvider\WhmcsRegistrar\src\Handle;
use WeDevelopCoffee\wPower\Controllers\BaseController;
use WeDevelopCoffee\wPower\Core\Core;

/**
 * Class ContactControllerView
 * @package OpenProvider\WhmcsRegistrar\Controllers\System
 */
class ContactController extends BaseController
{
    /**
     * @var API
     */
    private $API;
    /**
     * @var Domain
     */
    private $domain;
    /**
     * @var Handle
     */
    private $handle;

    /**
     * ConfigController constructor.
     */
    public function __construct(Core $core, API $API, Domain $domain, Handle $handle)
    {
        parent::__construct($core);

        $this->API = $API;
        $this->domain = $domain;
        $this->handle = $handle;
    }

    /**
     * Get the contact details.
     * @param $params
     * @return \OpenProvider\API\type
     */
    public function getDetails($params)
    {
        $params['sld'] = $params['original']['domainObj']->getSecondLevel();
        $params['tld'] = $params['original']['domainObj']->getTopLevel();

        try
        {
            $this->domain->load(array(
                'name'          =>  $params['sld'],
                'extension'     =>  $params['tld']
            ));

            $api                =   $this->API;
            $api->setParams($params);
            $values             =   $api->getContactDetails($this->domain);
        }
        catch (\Exception $e)
        {
            $values["error"] = $e->getMessage();
        }

        return $values;
    }

    /**
     * Save the contact details.
     * @param $params
     * @return array
     */
    public function saveDetails($params)
    {
        $params['sld'] = $params['original']['domainObj']->getSecondLevel();
        $params['tld'] = $params['original']['domainObj']->getTopLevel();

        try
        {
            $api                =   $this->API;
            $api->setParams($params);
            $handles            =   array_flip(\OpenProvider\API\APIConfig::$handlesNames);
            $this->domain->load(array(
                'name'          =>  $params['sld'],
                'extension'     =>  $params['tld']
            ));

            $handle = $this->handle;
            $handle->setApi($api);

            $customers['ownerHandle']   = $handle->updateOrCreate($params, 'registrant');
            $customers['adminHandle']   = $handle->updateOrCreate($params, 'admin');
            $customers['techHandle']    = $handle->updateOrCreate($params, 'tech');

            if(isset($params['contactdetails']['Billing']))
                $customers['billingHandle'] = $handle->updateOrCreate($params, 'billing');

            // Sleep for 10 seconds. Some registrars accept a new contact but do not process this immediately.
            sleep(10);

            $finalCustomers = [];

            // clean out the empty results
            array_walk($customers, function($handle, $key) use (&$customers, &$finalCustomers){
                if($handle != '')
                    $finalCustomers[$key] = $handle;
            });

            if(!empty($finalCustomers))
                $api->modifyDomainCustomers($this->domain, $finalCustomers);

            return ['success' => true];
        }
        catch (\Exception $e)
        {
            $values["error"] = $e->getMessage();
        }
        return $values;
    }
}