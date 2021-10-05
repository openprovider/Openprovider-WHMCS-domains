<?php

namespace OpenProvider\WhmcsRegistrar\Controllers\System;

use OpenProvider\API\APIConfig;
use OpenProvider\API\ApiHelper;
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
     * @var Domain
     */
    private $domain;
    /**
     * @var Handle
     */
    private $handle;
    /**
     * @var ApiHelper
     */
    private $apiHelper;

    /**
     * ConfigController constructor.
     */
    public function __construct(Core $core, Domain $domain, Handle $handle, ApiHelper $apiHelper)
    {
        parent::__construct($core);

        $this->domain = $domain;
        $this->handle = $handle;
        $this->apiHelper = $apiHelper;
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

        $this->domain->load(array(
            'name'          =>  $params['sld'],
            'extension'     =>  $params['tld']
        ));

        $values = $this->getContactDetails();

        if ($values['error']) {
            return $values;
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
            $this->domain->load(array(
                'name'          =>  $params['sld'],
                'extension'     =>  $params['tld']
            ));

            $handle = $this->handle;
            $handle->setApiHelper($this->apiHelper);

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

            if(!empty($finalCustomers)) {
                $domainOp = $this->apiHelper->getDomain($this->domain);
                $this->apiHelper->updateDomain($domainOp['id'], $finalCustomers);
            }

            return ['success' => true];
        }
        catch (\Exception $e)
        {
            $values["error"] = $e->getMessage();
        }
        return $values;
    }

    private function getContactDetails(): array
    {
        try {
            $domainOp = $this->apiHelper->getDomain($this->domain);
        } catch (\Exception $e) {
            return [
                'error' => $e->getMessage()
            ];
        }

        if (empty($domainOp)) {
            return [];
        }

        $contacts = [];
        foreach (APIConfig::$handlesNames as $key => $name) {
            if (empty($domainOp[$key])) {
                continue;
            }

            $customerOp = $this->apiHelper->getCustomer($domainOp[$key]) ?? false;

            if (!$customerOp) {
                continue;
            }

            $contacts[$name] = $customerOp;
        }

        unset($contacts['Reseller']);
        unset($contacts['reseller']);

        return $contacts;
    }
}
