<?php


namespace OpenProvider\WhmcsRegistrar\Controllers\System;


use OpenProvider\WhmcsRegistrar\enums\DatabaseTable;
use OpenProvider\WhmcsRegistrar\helpers\ApiResponse;
use OpenProvider\WhmcsRegistrar\helpers\DB as DBHelper;
use OpenProvider\OpenProvider;

use WeDevelopCoffee\wPower\Controllers\BaseController;
use WeDevelopCoffee\wPower\Core\Core;
use WeDevelopCoffee\wPower\Models\Registrar;

use WHMCS\Database\Capsule;

class ApiController extends BaseController
{
    /**
     * @var OpenProvider
     */
    private $openProvider;

    /**
     * ApiController constructor.
     */
    public function __construct(Core $core, OpenProvider $openProvider)
    {
        parent::__construct($core);
        $this->openProvider = $openProvider;
    }

    /**
     * Api function for update contacts tags.
     * Params is whmcs 'userid'.
     *
     * @param array $params [userId]
     */
    public function updateContactsTag($params)
    {
        $userId = (
                isset($params['userid'])
                && !empty($params['userid'])
                && is_int(intval($params['userid']))
            )
            ? intval($params['userid'])
            : false;

        $tag = '';
        if (DBHelper::checkTableExist(DatabaseTable::ClientTags))
            $tag = Capsule::table(DatabaseTable::ClientTags)
                ->where('clientid', $userId)
                ->first();

        $tags = $tag && $tag->tag
            ? [
                [
                    'key' => 'customer',
                    'value' => $tag->tag,
                ]
            ]
            : '';

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

        $domainDatabase = $this->_checkDomainExistInDatabase($params['domainId']);
        if (!$domainDatabase) {
            ApiResponse::error('Domain not found!');
            return;
        }

        $api = $this->openProvider->getApi();

        $action = $params['action'];
        $dnssecKey = [
            'flags'    => $params['flags'],
            'alg'      => $params['alg'],
            'protocol' => 3,
            'pub_key'   => $params['pubKey'],
        ];

        $domain = $this->openProvider->domain($domainDatabase->domain);
        // checking for duplicate dnssecKeys
        $dnssecKeys = [];
        $dnssecKeysHashes = [];
        try {
            $domainInfo = $api->getDomainRequest($domain);
            foreach($domainInfo['dnssec_keys'] as $dnssec) {
                $dnssecKeysHashes[] = md5($dnssec['flags'] . $dnssec['alg'] . $dnssec['protocol'] . trim($dnssec['pub_key']));
                $dnssecKeys[] = [
                    'flags'    => $dnssec['flags'],
                    'alg'      => $dnssec['alg'],
                    'protocol' => 3,
                    'pub_key'   => $dnssec['pub_key'],
                ];
            }
        } catch (\Exception $e) {}

        $modifiedDnsSecKeys = [];
        switch ($action) {
            case 'create':
                $modifiedDnsSecKeys = $this->_createDnssecRecord($dnssecKeys, $dnssecKeysHashes, $dnssecKey);
                break;
            case 'delete':
                $modifiedDnsSecKeys = $this->_deleteDnssecRecord($dnssecKeys, $dnssecKeysHashes, $dnssecKey);
                break;
        }

        // update dnssecKeys with new record,
        $args = [
            'dnssec_keys'     => $modifiedDnsSecKeys,
        ];

        if (count($modifiedDnsSecKeys) > 0)
            $args['is_dnssec_enabled'] = true;
        else
            $args['is_dnssec_enabled'] = false;

        try {

            $api->updateDomainRequest($domainInfo['id'], $args);
        } catch (\Exception $e) {
            ApiResponse::error(400, $e->getMessage());
            return;
        }
        ApiResponse::success(['dnssecKeys' => $args['dnssec_keys']]);
    }

    /**
     * Turn on|off dnssecKeys in openprovider
     *
     * @param array $params [domainId, isDnssecEnabled(1|0), ]
     */
    public function updateDnsSecEnabled($params)
    {
        if (!isset($params['domainId']) || empty($params['domainId'])) {
            ApiResponse::error(400, 'domain id is required!');
            return;
        }

        $domainDatabase = $this->_checkDomainExistInDatabase($params['domainId']);
        if (!$domainDatabase) {
            ApiResponse::error('Domain not found!');
            return;
        }

        $api = $this->openProvider->getApi();

        $isDnssecEnabled = $params['isDnssecEnabled'] == 'true';

        $domain = $this->openProvider->domain($domainDatabase->domain);

        $args = [
            'is_dnssec_enabled' => $isDnssecEnabled,
        ];

        try {
            $domainOp = $api->getDomainRequest($domain);

            $api->updateDomainRequest($domainOp['id'], $args);
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
     * @return mixed
     */
    private function _createDnsSecRecord($dnssecKeys, $dnssecKeysHashes, $dnssecKey)
    {
        if (in_array(md5($dnssecKey['flags'] . $dnssecKey['alg'] . strval(3) . trim($dnssecKey['pub_key'])), $dnssecKeysHashes))
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
     * @return mixed
     */
    private function _deleteDnsSecRecord($dnssecKeys, $dnssecKeysHashes, $dnssecKey)
    {
        $recordIndex = array_search(md5($dnssecKey['flags'] . $dnssecKey['alg'] . strval(3) . trim($dnssecKey['pub_key'])), $dnssecKeysHashes);
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
        $api = $this->openProvider->getApi();

        foreach ($contactsHandles as $contactHandle) {
            try {
                $api->updateCustomerTagsRequest($contactHandle, $tags);
            } catch (\Exception $e) {
                continue;
            }
        }
    }

    /**
     * Return domain by domain's Id from database or false
     *
     * @param int $domainId Domain id from database
     * @return false|databaseRow
     */
    private function _checkDomainExistInDatabase($domainId)
    {
        $domain = Capsule::table('tbldomains')
            ->where('id', $domainId)
            ->first();

        if ($domain)
            return $domain;

        return false;
    }
}

