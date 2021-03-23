<?php

namespace OpenProvider\WhmcsRegistrar\Controllers\System;

use OpenProvider\API\API;
use OpenProvider\API\APIConfig;
use OpenProvider\API\ApiInterface;
use OpenProvider\API\Domain;
use OpenProvider\WhmcsRegistrar\enums\DatabaseTable;
use OpenProvider\WhmcsRegistrar\helpers\DB as DBHelper;
use OpenProvider\WhmcsRegistrar\Models\Tld;
use OpenProvider\WhmcsRegistrar\src\Handle;
use WeDevelopCoffee\wPower\Controllers\BaseController;
use WeDevelopCoffee\wPower\Core\Core;
use WHMCS\Database\Capsule;

use OpenProvider\WhmcsRegistrar\helpers\Dictionary;

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
     * @var ApiInterface
     */
    private $apiClient;

    /**
     * ConfigController constructor.
     */
    public function __construct(Core $core, API $API, Domain $domain, Handle $handle, ApiInterface $apiClient)
    {
        parent::__construct($core);

        $this->API = $API;
        $this->apiClient = $apiClient;
        $this->domain = $domain;
        $this->handle = $handle;
    }

    /**
     * Get the contact details.
     * @param $params
     * @return array
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

            $values = $this->getContactDetails($this->domain);
        }
        catch (\Exception $e)
        {
            $values["error"] = $e->getMessage();
        }

        $domainTld = new Tld($params['tld']);
        array_walk($values, function (&$contact) use ($domainTld) {
            if (!$domainTld->isNeededShortState())
                return;

            switch ($contact['Country']) {
                case 'US':
                    $USStates = array_flip(Dictionary::get(Dictionary::USStates));
                    $USStateExist = !empty($contact['State'])
                        && in_array($contact['State'], array_keys($USStates));
                    if ($USStateExist)
                        $contact['State'] = $USStates[$contact['State']];

                    break;
                default:
                    break;
            }
        });

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

        $userTag = '';
        try {
            if (DBHelper::checkTableExist(DatabaseTable::ClientTags)) {
                $customerTag = Capsule::table(DatabaseTable::ClientTags)
                    ->where('clientid', $params['userid'])
                    ->first();
                if ($customerTag && $customerTag->tag)
                    $userTag = [$customerTag->tag];
            }
        } catch (\Exception $e) {}

        if (isset($params['contactdetails'])) {
            $contactDetails = &$params['contactdetails'];
            array_walk($contactDetails, function (&$contact) use ($userTag) {
                $contact['tags'] = $userTag;
            });
        }

        try
        {
            $api                =   $this->API;
            $api->setParams($params);
            $handles            =   array_flip(APIConfig::$handlesNames);
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
            sleep(2);

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

    private function getContactDetails($domain): array
    {
        try {
            $domainOp = $this->apiClient->call('searchDomainRequest', [
                'domainNamePattern' => $domain->name,
                'extension'         => $domain->extension,
            ])->getData()['results'][0];
        } catch (\Exception $e) {
            return [];
        }

        $contacts = [];
        foreach (APIConfig::$handlesNames as $key => $name) {
            if (empty($domainOp[$key])) {
                continue;
            }

            try {
                $customerOp = $this->apiClient->call('retrieveCustomerRequest', [
                    'handle' => $domainOp[$key]
                ])->getData();

                $customerInfo = [];
                $customerInfo['First Name'] = $customerOp['name']['firstName'];
                $customerInfo['Last Name'] = $customerOp['name']['lastName'];
                $customerInfo['Company Name'] = $customerOp['companyName'];
                $customerInfo['Email Address'] = $customerOp['email'];
                $customerInfo['Address'] = $customerOp['address']['street'] . ' ' .
                    $customerOp['address']['number'] . ' ' .
                    $customerOp['address']['suffix'];
                $customerInfo['City'] = $customerOp['address']['city'];
                $customerInfo['State'] = $customerOp['address']['state'];
                $customerInfo['Zip Code'] = $customerOp['address']['zipcode'];
                $customerInfo['Country'] = $customerOp['address']['country'];
                $customerInfo['Phone Number'] = $customerOp['phone']['countryCode'] . '.' .
                    $customerOp['phone']['areaCode'] .
                    $customerOp['phone']['subscriberNumber'];

                $contacts[$name] = $customerInfo;
            } catch (\Exception $e) {
                continue;
            }
        }

        unset($contacts['Reseller']);
        unset($contacts['reseller']);

        return $contacts;
    }
}
