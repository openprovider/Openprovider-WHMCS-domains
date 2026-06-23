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
            $values = $this->getContactDetails($params);
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

            $params = $this->addLanguageToContactDetails($params);

            if (isset($params['contactdetails']['Owner']))
                $customers['ownerHandle']   = $handle->updateOrCreate($params, 'registrant');
            if (isset($params['contactdetails']['Admin']))
                $customers['adminHandle']   = $handle->updateOrCreate($params, 'admin');
            if (isset($params['contactdetails']['Tech']))
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

    /**
     * Add language to contactdetails for roles using existing contacts (uXX format).
     *
     * @param array $params
     * @return array
     */
    private function addLanguageToContactDetails(array $params): array
    {
        // Safely fetch expected POST arrays.
        $wc  = filter_input(INPUT_POST, 'wc', FILTER_DEFAULT, FILTER_REQUIRE_ARRAY);
        $sel = filter_input(INPUT_POST, 'sel', FILTER_DEFAULT, FILTER_REQUIRE_ARRAY);

        if (
            empty($params['language']) ||
            empty($params['contactdetails']) ||
            !is_array($params['contactdetails']) ||
            empty($wc) ||
            empty($sel) ||
            !is_array($wc) ||
            !is_array($sel)
        ) {
            return $params;
        }
        foreach ($wc as $role => $mode) {
            // Validate role name format to avoid unexpected keys from user input.
            if (!is_string($role) || !preg_match('/^[a-zA-Z0-9_]+$/', $role)) {
                continue;
            }
            // Normalize mode to string and only allow expected value.
            if (!is_string($mode)) {
                $mode = (string) $mode;
            }
            // Only roles using existing contact
            if ($mode !== 'contact') {
                continue;
            }
            $selected = $sel[$role] ?? '';

            // Only when selected value is like "uXX"
            if (!is_string($selected) || !preg_match('/^u\d+$/', $selected)) {
                continue;
            }

            // Inject language into contactdetails
            if (!empty($params['language']) && isset($params['contactdetails'][$role]) && is_array($params['contactdetails'][$role])) {
                $params['contactdetails'][$role]['language'] = $params['language'];
            }
        }

        return $params;
    }

    /**
     * @param $params
     * @return array
     *
     * @throws \Exception
     */
    private function getContactDetails($params): array
    {
        $domainOp = $this->apiHelper->getDomain($this->domain);

        if (empty($domainOp)) {
            return [];
        }

        $tldMetaData = $this->apiHelper->getTldMeta($this->domain->extension);

        $handlesToFetch = [];
        foreach (APIConfig::$handlesNames as $key => $name) {
            $handleSupportedKey = $key . 'Supported';

            if (
                isset($tldMetaData[$handleSupportedKey]) &&
                $tldMetaData[$handleSupportedKey] &&
                !empty($domainOp[$key])
            ) {
                $handlesToFetch[$name] = $domainOp[$key];
            }
        }

        if (empty($handlesToFetch)) {
            return [];
        }

        // Parallel contacts fetch
        $contacts = $this->apiHelper->getCustomersAsync($handlesToFetch);

        unset($contacts['Reseller']);
        unset($contacts['reseller']);

        $this->syncHandlesWithWhmcs($params, $handlesToFetch, $contacts);

        return $contacts;
    }

    /**
     * Sync wHandles and wDomain_handle for the fetched OP contact handles.
     *
     * wHandles.type rules:
     *   - Owner is always type='all' (matches DomainController's findOrCreate default).
     *   - Any OP handle shared by more than one role is also type='all', so findExisting
     *     can locate it for any role search.
     *   - A handle serving exactly one non-Owner role gets that role's specific type.
     *
     * wDomain_handle always uses the specific role type (registrant/admin/tech/billing).
     * wHandles.data is never overwritten — existing rows may carry extensionAdditionalData.
     */
    private function syncHandlesWithWhmcs(array $params, array $handlesToFetch, array $contacts): void
    {
        try {
            $domainName  = ($params['sld'] ?? '') . '.' . ($params['tld'] ?? '');
            $whmcsDomain = Capsule::table('tbldomains')
                ->where('domain', $domainName)
                ->first(['id', 'userid']);

            if (!$whmcsDomain) {
                return;
            }

            $domainId = (int) $whmcsDomain->id;
            $userId   = (int) $whmcsDomain->userid;

            $roleToType = [
                'Owner'   => 'registrant',
                'Admin'   => 'admin',
                'Tech'    => 'tech',
                'Billing' => 'billing',
            ];

            // Keep only roles that have both a handle ID and contact data.
            $validRoles = array_filter($handlesToFetch, function ($handleId, $roleName) use ($contacts) {
                return !empty($handleId) && !empty($contacts[$roleName]);
            }, ARRAY_FILTER_USE_BOTH);

            if (empty($validRoles)) {
                return;
            }

            // How many roles each OP handle ID serves — used to decide wHandles.type.
            $handleRoleCounts = array_count_values(array_values($validRoles));

            $handleDbIds = [];
            foreach ($validRoles as $roleName => $handleId) {
                $pivotType = $roleToType[$roleName] ?? strtolower($roleName);

                // Owner is always type='all' (matches DomainController's findOrCreate($params)
                // default). Any handle shared by more than one role is also type='all' so
                // findExisting can locate it regardless of which role type is searched.
                $wHandleType = ($roleName === 'Owner' || $handleRoleCounts[$handleId] > 1)
                    ? 'all'
                    : $pivotType;

                if (!isset($handleDbIds[$handleId])) {
                    $handleDbIds[$handleId] = $this->ensureWHandleRow(
                        $handleId, $userId, $wHandleType, $roleName, $contacts[$roleName]
                    );
                }

                $this->syncDomainHandleLink($domainId, $handleDbIds[$handleId], $pivotType);
            }
        } catch (\Exception $e) {
            logModuleCall('Openprovider NL', 'syncHandlesWithWhmcs', $params, $e->getMessage());
        }
    }

    /**
     * Return the wHandles.id for the given OP handle, creating the row if absent.
     * If the row exists with a different type, the type is corrected so findExisting
     * can locate it for the right role. The data column is never overwritten — it may
     * carry extensionAdditionalData set during register/transfer.
     */
    private function ensureWHandleRow(string $handleId, int $userId, string $type, string $roleName, array $contact): int
    {
        if ($type === 'all') {
            // Prefer an existing 'all' row — if one already exists (e.g. created by
            // findOrCreate during registration) use it directly. Only fall back to a
            // specific-type row when no 'all' row exists, so we can upgrade it rather
            // than creating a second row and leaving the first orphaned.
            $row = Capsule::table('wHandles')
                ->where('handle', $handleId)
                ->where('user_id', $userId)
                ->where('registrar', 'openprovider')
                ->orderByRaw("CASE WHEN type = 'all' THEN 0 ELSE 1 END")
                ->first();
        } else {
            // Same pattern as findExisting: match exact type OR 'all' in one query.
            $row = Capsule::table('wHandles')
                ->where('handle', $handleId)
                ->where('user_id', $userId)
                ->where('registrar', 'openprovider')
                ->where(function ($q) use ($type) {
                    $q->where('type', $type)->orWhere('type', 'all');
                })
                ->first();
        }

        if ($row) {
            // Only upgrade to 'all' — never downgrade from 'all' to a specific type,
            // and never change between specific types. Downgrading breaks findExisting
            // for other roles that relied on the broader type.
            if ($row->type !== 'all' && $type === 'all') {
                Capsule::table('wHandles')
                    ->where('id', $row->id)
                    ->update(['type' => 'all', 'updated_at' => date('Y-m-d H:i:s')]);
            }
            return (int) $row->id;
        }

        $customerObj = new \OpenProvider\API\Customer(
            ['contactdetails' => [ucfirst($roleName) => $contact]],
            strtolower($roleName)
        );
        $now = date('Y-m-d H:i:s');

        return (int) Capsule::table('wHandles')->insertGetId([
            'handle'     => $handleId,
            'user_id'    => $userId,
            'registrar'  => 'openprovider',
            'type'       => $type,
            'data'       => serialize($customerObj),
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    /**
     * Ensure a wDomain_handle row exists for the given domain/type pointing to $handleDbId.
     * Creates when missing; updates when it points to a different handle.
     */
    private function syncDomainHandleLink(int $domainId, int $handleDbId, string $type): void
    {
        $link = Capsule::table('wDomain_handle')
            ->where('domain_id', $domainId)
            ->where('type', $type)
            ->first();

        if (!$link) {
            $now = date('Y-m-d H:i:s');
            Capsule::table('wDomain_handle')->insert([
                'domain_id'  => $domainId,
                'handle_id'  => $handleDbId,
                'type'       => $type,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        } elseif ((int) $link->handle_id !== $handleDbId) {
            Capsule::table('wDomain_handle')
                ->where('domain_id', $domainId)
                ->where('type', $type)
                ->update(['handle_id' => $handleDbId, 'updated_at' => date('Y-m-d H:i:s')]);
        }
    }
}
