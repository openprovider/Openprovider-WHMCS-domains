<?php

namespace OpenProvider\WhmcsRegistrar\Controllers\System;

use OpenProvider\API\Domain;
use OpenProvider\OpenProvider;
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
     * @var OpenProvider
     */
    private $openProvider;
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
    public function __construct(Core $core, Domain $domain, Handle $handle)
    {
        parent::__construct($core);

        $this->openProvider = new OpenProvider();
        $this->domain = $domain;
        $this->handle = $handle;
    }


    /**
     * @param $params
     * @return array
     */
    public function getDetails($params)
    {
        $api = $this->openProvider->getApi();

        $this->domain = $this->openProvider->domain($params['domain']);
        try
        {
            $values = $api->getDomainContactsRequest($this->domain);
        }
        catch (\Exception $e)
        {
            $values["error"] = $e->getMessage();
        }

        $domainTld = new Tld($this->domain->extension);
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
        $api = $this->openProvider->getApi();

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
            $this->domain = $this->openProvider->domain($params['domain']);

            $handle = $this->handle;
            $handle->setApi($api);

            $customers['owner_handle']   = $handle->updateOrCreate($params, 'registrant');
            $customers['admin_handle']   = $handle->updateOrCreate($params, 'admin');
            $customers['tech_handle']    = $handle->updateOrCreate($params, 'tech');

            if(isset($params['contactdetails']['Billing']))
                $customers['billing_handle'] = $handle->updateOrCreate($params, 'billing');

            // Sleep for 10 seconds. Some registrars accept a new contact but do not process this immediately.
            sleep(2);

            $finalCustomers = [];
            // clean out the empty results
            array_walk($customers, function($handle, $key) use (&$customers, &$finalCustomers){
                if($handle != '')
                    $finalCustomers[$key] = $handle;
            });

            if(!empty($finalCustomers)) {
                $domain = $api->getDomainRequest($this->domain);
                $api->updateDomainRequest($domain['id'], $finalCustomers);
            }

            return ['success' => true];
        }
        catch (\Exception $e)
        {
            $values["error"] = $e->getMessage();
        }
        return $values;
    }
}