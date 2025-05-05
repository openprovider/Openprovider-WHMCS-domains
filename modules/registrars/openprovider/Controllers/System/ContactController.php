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
     *
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

        try {
            $values = $this->getContactDetails($this->domain);
        } catch (\Exception $e) {
            return [
                'error' => $e->getMessage()
            ];
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

            $contactTypes = ['Owner' => 'ownerHandle', 'Admin' => 'adminHandle', 'Tech' => 'techHandle'];
            if (isset($params['contactdetails']['Billing'])) {
                $contactTypes['Billing'] = 'billingHandle';
            }

            $handles = [];
            $contactDataHashes = [];

            foreach ($contactTypes as $type => $handleKey) {
                $data = $params['contactdetails'][$type] ?? [];
                $normalized = json_encode(array_map('trim', $data)); // normalize and stringify for hash comparison

                $contactRoleType = strtolower($type);
                if ($contactRoleType == 'owner') {
                    $contactRoleType = 'registrant';
                }

                $ownHandle = $handle->checkHandleEquality($params, $contactRoleType);

                if ($ownHandle !== false) {
                    $customers[$handleKey] = $ownHandle;
                    $handles[$handleKey] = $customers[$handleKey];
                }
                // Try to reuse existing handle
                elseif ($existingKey = array_search($normalized, $contactDataHashes)) {
                    // reuse handle
                    $customers[$handleKey] = $handles[$existingKey];
                    $handles[$handleKey] = $customers[$handleKey];
                } else {
                    // create/update handle
                    $customers[$handleKey] = $handle->updateOrCreate($params, $contactRoleType);
                    $contactDataHashes[$handleKey] = $normalized;
                    $handles[$handleKey] = $customers[$handleKey];
                }
            }

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

    /**
     * @return array
     *
     * @throws \Exception
     */
    private function getContactDetails(): array
    {
        $domainOp = $this->apiHelper->getDomain($this->domain);

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
