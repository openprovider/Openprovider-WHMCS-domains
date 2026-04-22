<?php

namespace OpenProvider\WhmcsDomainAddon\Services\BulkTransfer;

use OpenProvider\API\ApiHelper;
use OpenProvider\API\ApiV1;
use OpenProvider\API\Domain;
use OpenProvider\API\DomainTransfer;
use OpenProvider\WhmcsRegistrar\helpers\DbCacheHelper;
use OpenProvider\WhmcsRegistrar\src\Configuration;
use OpenProvider\WhmcsRegistrar\src\Handle as RegistrarHandle;
use WHMCS\Database\Capsule;

class OpenproviderTransferClient
{
    private const TLD_METADATA_CACHE_TTL = 60 * 60 * 24;
    private const ACTIVE_TRANSFER_STATUSES = ['ACT'];
    private const PENDING_TRANSFER_STATUSES = ['REQ', 'PEN', 'SCH', 'RRQ'];
    private const FAILED_TRANSFER_STATUSES = ['FAI', 'REJ', 'DEL'];

    /**
     * @var mixed
     */
    protected $launcher;

    public function createOrReuseTransferHandles(array $params)
    {
        $handleService = $this->getLauncher()->get(RegistrarHandle::class);
        $apiHelper = $this->getApiHelper();
        $handleService->setApiHelper($apiHelper);
        $tldMetaData = $this->getTransferTldMetaData($params);

        $handles = [
            'owner_handle' => null,
            'admin_handle' => null,
            'tech_handle' => null,
            'billing_handle' => null,
        ];

        $hasSupportedHandle = !empty($tldMetaData['ownerHandleSupported'])
            || !empty($tldMetaData['adminHandleSupported'])
            || !empty($tldMetaData['techHandleSupported'])
            || !empty($tldMetaData['billingHandleSupported']);

        if (!$hasSupportedHandle) {
            return $handles;
        }

        $this->assertSupportedHandleContactDetailsExist($params, $tldMetaData);

        $allContactsMatchOwner = $this->allContactsMatchOwner($params); // This returns true either if all contacts match owner or only has owner contact

        if ($allContactsMatchOwner) {
            $sharedHandle = $this->createHandle($handleService, $params);

            if (!empty($tldMetaData['ownerHandleSupported'])) {
                $handles['owner_handle'] = $sharedHandle;
            }

            if (!empty($tldMetaData['adminHandleSupported'])) {
                $handles['admin_handle'] = $sharedHandle;
            }

            if (!empty($tldMetaData['techHandleSupported'])) {
                $handles['tech_handle'] = $sharedHandle;
            }

            if (!empty($tldMetaData['billingHandleSupported'])) {
                $handles['billing_handle'] = $sharedHandle;
            }

            $this->syncDomainHandleAssignments($params, $handles);

            return $handles;
        }

        // Process for at least one supported role has contact data that differs from owner.
        $handlesByContactSignature = [];

        if (!empty($tldMetaData['ownerHandleSupported'])) {
            $ownerContact = $this->getContactDetailsByRole($params, 'Owner');
            $ownerSignature = $this->getContactSignature($ownerContact);
            $handles['owner_handle'] = $this->createHandle($handleService, $params, 'registrant');
            $handlesByContactSignature[$ownerSignature] = $handles['owner_handle'];
        }

        $roleMap = [
            'admin_handle' => ['supported' => 'adminHandleSupported', 'contact' => 'Admin', 'type' => 'admin'],
            'tech_handle' => ['supported' => 'techHandleSupported', 'contact' => 'Tech', 'type' => 'tech'],
            'billing_handle' => ['supported' => 'billingHandleSupported', 'contact' => 'Billing', 'type' => 'billing'],
        ];

        foreach ($roleMap as $handleKey => $roleConfig) {
            if (empty($tldMetaData[$roleConfig['supported']])) {
                continue;
            }

            $contactDetails = $this->getContactDetailsByRole($params, $roleConfig['contact']);
            if (empty($contactDetails)) {
                throw new \RuntimeException(sprintf(
                    'Missing WHOIS contact details for supported %s handle creation.',
                    strtolower($roleConfig['contact'])
                ));
            }

            $contactSignature = $this->getContactSignature($contactDetails);

            if (isset($handlesByContactSignature[$contactSignature])) {
                $handles[$handleKey] = $handlesByContactSignature[$contactSignature];
                continue;
            }

            $handles[$handleKey] = $this->createHandle($handleService, $params, $roleConfig['type']);
            $handlesByContactSignature[$contactSignature] = $handles[$handleKey];
        }

        $this->syncDomainHandleAssignments($params, $handles);

        return $handles;
    }

    public function transferDomain(array $payload)
    {
        $domainTransfer = new DomainTransfer();
        $domainTransfer->domain = new Domain([
            'name' => $payload['domain']['name'],
            'extension' => $payload['domain']['extension'],
        ]);
        $domainTransfer->authCode = $payload['auth_code'];
        $domainTransfer->ownerHandle = $payload['owner_handle'];
        $domainTransfer->adminHandle = $payload['admin_handle'] ?? null;
        $domainTransfer->techHandle = $payload['tech_handle'] ?? null;
        $domainTransfer->billingHandle = $payload['billing_handle'] ?? null;
        $domainTransfer->autorenew = $payload['autorenew'] ?? 'default';
        $domainTransfer->isPrivateWhoisEnabled = $payload['is_private_whois_enabled'] ?? false;
        $domainTransfer->isDnssecEnabled = $payload['is_dnssec_enabled'] ?? false;
        // $domainTransfer->nameServers = $payload['name_servers'] ?? null;
        // $domainTransfer->additionalData = $payload['additional_data'] ?? null;
        // $domainTransfer->nsTemplateName = $payload['ns_template_name'] ?? null;
        // $domainTransfer->useDomicile = $payload['use_domicile'] ?? false;
        $domainTransfer->importNameserversFromRegistry = $payload['import_nameservers_from_registry'] ?? false;

        $response = $this->getApiHelper()->transferDomain($domainTransfer);

        $this->assertSuccessfulTransferResponse($response);

        return $response;
    }

    public function getDomainDetails($domainName, $extension, array $additionalArgs = [])
    {
        $domain = new Domain([
            'name' => $domainName,
            'extension' => ltrim((string) $extension, '.'),
        ]);

        return $this->getApiHelper()->getDomain($domain, $additionalArgs);
    }

    public function normalizeTransferState(array $response)
    {
        return [
            'status' => !empty($response['status']) ? strtoupper((string) $response['status']) : null,
            'domain_id' => $response['id'] ?? null,
            'renewal_date' => $response['renewalDate'] ?? $response['renewal_date'] ?? null,
            'expiration_date' => $response['expirationDate'] ?? $response['expiration_date'] ?? null,
            'message' => $this->extractTransferErrorMessage($response, ''),
            'raw' => $response,
        ];
    }

    public function isActiveTransferStatus($status)
    {
        return in_array(strtoupper((string) $status), self::ACTIVE_TRANSFER_STATUSES, true);
    }

    public function isPendingTransferStatus($status)
    {
        return in_array(strtoupper((string) $status), self::PENDING_TRANSFER_STATUSES, true);
    }

    public function isFailedTransferStatus($status)
    {
        return in_array(strtoupper((string) $status), self::FAILED_TRANSFER_STATUSES, true);
    }

    protected function createHandle(RegistrarHandle $handleService, array $params, $type = 'all')
    {
        $handle = $type === 'all'
            ? $handleService->findOrCreate($params)
            : $handleService->findOrCreate($params, $type);

        if (empty($handle)) {
            throw new \RuntimeException('Openprovider handle creation returned an empty handle.');
        }

        return $handle;
    }

    protected function assertSupportedHandleContactDetailsExist(array $params, array $tldMetaData)
    {
        $roleMap = [
            'ownerHandleSupported' => 'Owner',
            'adminHandleSupported' => 'Admin',
            'techHandleSupported' => 'Tech',
            'billingHandleSupported' => 'Billing',
        ];

        foreach ($roleMap as $supportedKey => $contactRole) {
            if (empty($tldMetaData[$supportedKey])) {
                continue;
            }

            if (!empty($this->getContactDetailsByRole($params, $contactRole))) {
                continue;
            }

            throw new \RuntimeException(sprintf(
                'Missing WHOIS contact details for supported %s handle creation.',
                strtolower($contactRole)
            ));
        }
    }

    protected function syncDomainHandleAssignments(array $params, array $handles)
    {
        if (empty($params['domainid'])) {
            return;
        }

        $domainId = (int) $params['domainid'];
        $roleHandles = [
            'registrant' => $handles['owner_handle'] ?? null,
            'admin' => $handles['admin_handle'] ?? null,
            'tech' => $handles['tech_handle'] ?? null,
            'billing' => $handles['billing_handle'] ?? null,
        ];

        $existingHandleIds = $this->getExistingDomainHandleAssignments($domainId);
        $resolvedHandleIds = [];

        foreach ($roleHandles as $roleType => $handle) {
            if (empty($handle)) {
                continue;
            }

            $handleId = $this->findWhmcsHandleId($params, $handle, $roleType);
            if ($handleId === null) {
                throw new \RuntimeException(sprintf(
                    'Unable to find WHMCS handle row for %s handle %s.',
                    $roleType,
                    $handle
                ));
            }

            $resolvedHandleIds[$roleType] = $handleId;
        }

        $fallbackHandleId = $resolvedHandleIds['registrant'] ?? reset($resolvedHandleIds);
        if (empty($fallbackHandleId)) {
            $fallbackHandleId = reset($existingHandleIds);
        }

        if (empty($fallbackHandleId)) {
            return;
        }

        $syncRows = [];
        foreach (array_keys($roleHandles) as $roleType) {
            $handleId = $resolvedHandleIds[$roleType]
                ?? $existingHandleIds[$roleType]
                ?? $fallbackHandleId;

            $syncRows[] = [
                'domain_id' => $domainId,
                'handle_id' => $handleId,
                'type' => $roleType,
            ];
        }

        Capsule::connection()->transaction(function () use ($domainId, $syncRows) {
            Capsule::table('wDomain_handle')
                ->where('domain_id', $domainId)
                ->delete();

            Capsule::table('wDomain_handle')->insert($syncRows);
        });
    }

    protected function getExistingDomainHandleAssignments($domainId)
    {
        $assignments = [];
        $rows = Capsule::table('wDomain_handle')
            ->where('domain_id', (int) $domainId)
            ->get();

        foreach ($rows as $row) {
            if (empty($row->type) || empty($row->handle_id)) {
                continue;
            }

            $assignments[(string) $row->type] = (int) $row->handle_id;
        }

        return $assignments;
    }

    protected function findWhmcsHandleId(array $params, $handle, $roleType)
    {
        $query = Capsule::table('wHandles')
            ->where('registrar', 'openprovider')
            ->where('handle', (string) $handle);

        if (!empty($params['userid'])) {
            $query->where('user_id', (int) $params['userid']);
        }

        $rows = $query->get();
        if (empty($rows)) {
            return null;
        }

        $preferredTypes = array_unique([$roleType, 'all', 'registrant', 'admin', 'tech', 'billing']);
        foreach ($preferredTypes as $preferredType) {
            foreach ($rows as $row) {
                if ((string) ($row->type ?? '') === $preferredType) {
                    return (int) $row->id;
                }
            }
        }

        return null;
    }

    protected function allContactsMatchOwner(array $params)
    {
        $ownerContact = $this->getContactDetailsByRole($params, 'Owner');
        $ownerSignature = $this->getContactSignature($ownerContact);

        foreach (['Admin', 'Tech', 'Billing'] as $role) {
            $contactDetails = $this->getContactDetailsByRole($params, $role);
            if (empty($contactDetails)) {
                continue;
            }

            if ($this->getContactSignature($contactDetails) !== $ownerSignature) {
                return false;
            }
        }

        return true;
    }

    protected function getContactDetailsByRole(array $params, $role)
    {
        if (empty($params['contactdetails'][$role]) || !is_array($params['contactdetails'][$role])) {
            return [];
        }

        return $params['contactdetails'][$role];
    }

    protected function getContactSignature(array $contactDetails)
    {
        $normalizedContactDetails = [];

        foreach ($contactDetails as $key => $value) {
            $normalizedKey = strtolower(trim((string) $key));
            $normalizedContactDetails[$normalizedKey] = is_string($value)
                ? trim($value)
                : $value;
        }

        ksort($normalizedContactDetails);

        return md5(json_encode($normalizedContactDetails));
    }

    protected function getTransferTldMetaData(array $params)
    {
        $tld = ltrim((string) ($params['tld'] ?? ''), '.');

        return DbCacheHelper::remember(
            'tld_meta_' . $tld,
            $this->resolveCacheMode($params),
            self::TLD_METADATA_CACHE_TTL,
            function () use ($tld) {
                return $this->getApiHelper()->getTldMeta($tld);
            }
        );
    }

    protected function assertSuccessfulTransferResponse($response)
    {
        if (!is_array($response)) {
            throw new \RuntimeException('Openprovider transfer returned an unexpected response.');
        }

        $error = $this->extractTransferError($response);
        if ($error !== null) {
            throw new \RuntimeException($error);
        }
    }

    protected function extractTransferError(array $response)
    {
        $error = $this->getNestedArrayValue($response, ['error'])
            ?? $this->getNestedArrayValue($response, ['data', 'error']);

        if (is_string($error) && trim($error) !== '') {
            return trim($error);
        }

        $negativeStatuses = ['error', 'failed', 'failure', 'rejected', 'cancelled'];

        $status = $this->getNestedArrayValue($response, ['status']);
        if (!is_string($status) || trim($status) === '') {
            $status = $this->getNestedArrayValue($response, ['data', 'status']);
        }

        if (is_string($status) && in_array(strtolower(trim($status)), $negativeStatuses, true)) {
            return $this->extractTransferErrorMessage($response, 'Openprovider transfer returned a failed status response.');
        }

        $result = $this->getNestedArrayValue($response, ['result']);
        if (!is_string($result) || trim($result) === '') {
            $result = $this->getNestedArrayValue($response, ['data', 'result']);
        }

        if (is_string($result) && in_array(strtolower(trim($result)), ['error', 'failed', 'failure'], true)) {
            return $this->extractTransferErrorMessage($response, 'Openprovider transfer returned a failed result response.');
        }

        $success = $this->getNestedArrayValue($response, ['success']);
        if (!is_bool($success)) {
            $success = $this->getNestedArrayValue($response, ['data', 'success']);
        }

        if ($success === false) {
            return $this->extractTransferErrorMessage($response, 'Openprovider transfer returned an unsuccessful response.');
        }

        return null;
    }

    protected function extractTransferErrorMessage(array $response, $fallbackMessage)
    {
        foreach (
            [
                ['message'],
                ['desc'],
                ['description'],
                ['data', 'message'],
                ['data', 'desc'],
                ['data', 'description'],
                ['data', 'error'],
            ] as $path
        ) {
            $value = $this->getNestedArrayValue($response, $path);
            if (is_string($value) && trim($value) !== '') {
                return trim($value);
            }
        }

        return $fallbackMessage;
    }

    protected function getNestedArrayValue(array $data, array $path)
    {
        $value = $data;

        foreach ($path as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return null;
            }

            $value = $value[$segment];
        }

        return $value;
    }

    protected function getApiHelper()
    {
        return $this->getLauncher()->get(ApiHelper::class);
    }

    protected function getApiV1()
    {
        return $this->getLauncher()->get(ApiV1::class);
    }

    protected function resolveCacheMode(array $params)
    {
        if (($params['test_mode'] ?? false) === 'on') {
            return 'test';
        }

        $host = $this->getApiV1()->getConfiguration()->getHost();

        return $host === Configuration::get('restapi_url_sandbox') ? 'test' : 'live';
    }

    protected function getLauncher()
    {
        if ($this->launcher) {
            return $this->launcher;
        }

        $rootDir = $this->getWhmcsRootDir();
        require_once $rootDir . DIRECTORY_SEPARATOR . 'modules' . DIRECTORY_SEPARATOR . 'registrars' . DIRECTORY_SEPARATOR . 'openprovider' . DIRECTORY_SEPARATOR . 'init.php';
        require_once $rootDir . DIRECTORY_SEPARATOR . 'modules' . DIRECTORY_SEPARATOR . 'registrars' . DIRECTORY_SEPARATOR . 'openprovider' . DIRECTORY_SEPARATOR . 'openprovider.php';

        $core = openprovider_registrar_core();
        $core->launch();

        $this->launcher = openprovider_bind_required_classes($core->launcher);

        return $this->launcher;
    }

    protected function getWhmcsRootDir()
    {
        if (defined('ROOTDIR')) {
            return ROOTDIR;
        }

        return realpath(__DIR__ . '/../../../../../');
    }
}
