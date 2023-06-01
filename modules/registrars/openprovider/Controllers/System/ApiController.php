<?php

namespace OpenProvider\WhmcsRegistrar\Controllers\System;

use OpenProvider\API\ApiHelper;
use OpenProvider\API\Customer;
use OpenProvider\WhmcsRegistrar\enums\DatabaseTable;
use OpenProvider\WhmcsRegistrar\helpers\ApiResponse;
use OpenProvider\WhmcsRegistrar\helpers\DB as DBHelper;
use OpenProvider\WhmcsRegistrar\helpers\DomainFullNameToDomainObject;
use WeDevelopCoffee\wPower\Controllers\BaseController;
use WeDevelopCoffee\wPower\Core\Core;
use WHMCS\Database\Capsule;
use WHMCS\Authentication\CurrentUser;

class ApiController extends BaseController
{
    /**
     * @var ApiHelper
     */
    private $apiHelper;

    /**
     * ApiController constructor.
     */
    public function __construct(Core $core, ApiHelper $apiHelper)
    {
        parent::__construct($core);
        $this->apiHelper = $apiHelper;
    }

    /**
     * Api function for update contacts tags.
     * Params is whmcs 'userid'.
     *
     * @param array $params
     */
    public function updateContactsTag($params)
    {
        $currentUser = new CurrentUser();
        $authUser = $currentUser->user();
        $selectedClient = $currentUser->client();

        if (!$authUser || !$selectedClient) {
            ApiResponse::error(400, 'You are have no authority to make this request.');
            return;
        }

        $userId = $selectedClient->id;

        $tag = '';
        if (DBHelper::checkTableExist(DatabaseTable::ClientTags))
            $tag = Capsule::table(DatabaseTable::ClientTags)
                ->where('clientid', $userId)
                ->first();

        $tags = $tag && $tag->tag ?
            [$tag->tag] :
            '';

        $usersContacts = Capsule::table('wHandles')
            ->where([
                ['user_id', '=', $userId],
                ['registrar', '=', 'openprovider']
            ])
            ->select('handle')
            ->get()
            ->map(function ($contact) {
                return $contact->handle;
            });

        $this->_modifyContactsTag($usersContacts, $tags);

        ApiResponse::success();
    }

    /**
     * Update DnssecKeys records by action(delete existed or add new)
     *
     * @param array $params [domainId, action(create|delete), flags, alg, pubKey]
     */
    public function updateDnsSecRecord($params)
    {
        if (!isset($params['domainId']) || empty($params['domainId'])) {
            ApiResponse::error(400, 'domain id is required!');
            return;
        }

        $currentUser = new CurrentUser();
        $authUser = $currentUser->user();
        $selectedClient = $currentUser->client();

        if (!$authUser || !$selectedClient) {
            ApiResponse::error(400, 'You are have no authority to make this request.');
            return;
        }
        $domainDB = $this->_checkDomainExistInDatabase($params['domainId'], $selectedClient->id);

        if (!$domainDB) {
            ApiResponse::error('Domain not found!');
            return;
        }

        $action = $params['action'];
        $dnssecKey = [
            'flags'    => $params['flags'],
            'alg'      => $params['alg'],
            'protocol' => 3,
            'pubKey'   => $params['pubKey'],
        ];
        $domain = DomainFullNameToDomainObject::convert($domainDB->domain);

        try {
            $domainOP = $this->apiHelper->getDomain($domain);
        } catch (\Exception $ex) {
            throw new \Exception('Domain not exist in openprovider!');
        }

        // checking for duplicate dnssecKeys
        $dnssecKeys = [];
        $dnssecKeysHashes = [];
        foreach($domainOP['dnssecKeys'] as $dnssec) {
            $dnssecKeysHashes[] = md5($dnssec['flags'] . $dnssec['alg'] . $dnssec['protocol'] . trim($dnssec['pubKey']));
            $dnssecKeys[] = [
                'flags'    => $dnssec['flags'],
                'alg'      => $dnssec['alg'],
                'protocol' => 3,
                'pubKey'   => $dnssec['pubKey'],
            ];
        }

        $modifiedDnsSecKeys = [];
        switch ($action) {
            case 'create':
                $modifiedDnsSecKeys = $this->_createDnssecRecord($dnssecKeys, $dnssecKeysHashes, $dnssecKey);
                break;
            case 'delete':
                $modifiedDnsSecKeys = $this->_deleteDnssecRecord($dnssecKeys, $dnssecKeysHashes, $dnssecKey);
                break;
        }

        $args = [
            'dnssecKeys'      => $modifiedDnsSecKeys,
            'isDnssecEnabled' => count($modifiedDnsSecKeys) > 0,
        ];

        try {
            $this->apiHelper->updateDomain($domainOP['id'], $args);
        } catch (\Exception $e) {
            ApiResponse::error($e->getCode(), $e->getMessage());
            return;
        }
        ApiResponse::success(['dnssecKeys' => $args['dnssecKeys']]);
    }

    /**
     * Turn on|off dnssecKeys in openprovider
     *
     * @param array $params [domainId, isDnssecEnabled(1|0), ]
     */
    public function updateDnsSecEnabled(array $params)
    {
        if (!isset($params['domainId']) || empty($params['domainId'])) {
            ApiResponse::error(400, 'domain id is required!');
            return;
        }

        $currentUser = new CurrentUser();
        $authUser = $currentUser->user();
        $selectedClient = $currentUser->client();

        if (!$authUser || !$selectedClient) {
            ApiResponse::error(400, 'You are have no authority to make this request.');
            return;
        }

        $domainDB = $this->_checkDomainExistInDatabase($params['domainId'], $selectedClient->id);
        if (!$domainDB) {
            ApiResponse::error('Domain not found!');
            return;
        }

        $domain = DomainFullNameToDomainObject::convert($domainDB->domain);
        $args = [
            'isDnssecEnabled' => (bool)$params['isDnssecEnabled'],
        ];

        try {
            $domainOp = $this->apiHelper->getDomain($domain);
            $this->apiHelper->updateDomain($domainOp['id'], $args);
        } catch (\Exception $e) {
            ApiResponse::error(400, $e->getMessage());
            return;
        }

        ApiResponse::success();
    }

    /**
     * Return array without duplicates dnssecKeys to save it in openprovider.
     *
     * @param array $dnssecKeys already existed keys
     * @param array $dnssecKeysHashes hashes of already existed keys
     * @param array $dnssecKey new key to add
     * @return array
     */
    private function _createDnsSecRecord(array $dnssecKeys, array $dnssecKeysHashes, array $dnssecKey)
    {
        if (in_array(md5($dnssecKey['flags'] . $dnssecKey['alg'] . strval(3) . trim($dnssecKey['pubKey'])), $dnssecKeysHashes))
            return $dnssecKeys;

        $dnssecKeys[] = $dnssecKey;
        return $dnssecKeys;
    }

    /**
     * Return array without input dnssecKey.
     *
     * @param array $dnssecKeys already existed keys
     * @param array $dnssecKeysHashes hashes of already existed keys
     * @param array $dnssecKey key to remove from existed keys array
     * @return array
     */
    private function _deleteDnsSecRecord(array $dnssecKeys, array $dnssecKeysHashes, array $dnssecKey)
    {
        $recordIndex = array_search(md5($dnssecKey['flags'] . $dnssecKey['alg'] . strval(3) . trim($dnssecKey['pubKey'])), $dnssecKeysHashes);
        if ($recordIndex === false)
            return $dnssecKeys;

        unset($dnssecKeys[$recordIndex]);
        return array_values($dnssecKeys);
    }

    /**
     * Update tag of all contacts by contacts handles
     *
     * @param $contactsHandles
     * @param string $tags
     */
    private function _modifyContactsTag($contactsHandles, $tags = '')
    {
        foreach ($contactsHandles as $contactHandle) {
            try {
                $customer = new Customer(['tags' => $tags, 'handle' => $contactHandle]);
                $this->apiHelper->updateCustomer($contactHandle, $customer);
            } catch (\Exception $e) {
                continue;
            }
        }
    }

    /**
     * Return domain by domain's Id from database or false
     *
     * @param int $domainId Domain id from database
     * @param int $userId User id from database
     * @return false|object
     */
    private function _checkDomainExistInDatabase(int $domainId, int $userId)
    {
        $domain = Capsule::table('tbldomains')
            ->where('id', $domainId)
            ->where('userid', $userId)
            ->first();

        if ($domain)
            return $domain;

        return false;
    }
}
