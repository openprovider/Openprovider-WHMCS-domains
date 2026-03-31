<?php

namespace OpenProvider\WhmcsDomainAddon\Services\BulkTransfer;

use OpenProvider\API\ApiHelper;
use OpenProvider\API\ApiV1;
use OpenProvider\API\Domain;
use OpenProvider\API\DomainTransfer;
use OpenProvider\WhmcsRegistrar\helpers\DbCacheHelper;
use OpenProvider\WhmcsRegistrar\src\Configuration;
use OpenProvider\WhmcsRegistrar\src\Handle as RegistrarHandle;

class OpenproviderTransferClient
{
    private const TLD_METADATA_CACHE_TTL = 60 * 60 * 24;

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

        $allContactsMatchOwner = $this->allContactsMatchOwner($params);

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

            return $handles;
        }

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
            $contactSignature = $this->getContactSignature($contactDetails);

            if (isset($handlesByContactSignature[$contactSignature])) {
                $handles[$handleKey] = $handlesByContactSignature[$contactSignature];
                continue;
            }

            $handles[$handleKey] = $this->createHandle($handleService, $params, $roleConfig['type']);
            $handlesByContactSignature[$contactSignature] = $handles[$handleKey];
        }

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

        $this->getApiHelper()->transferDomain($domainTransfer);
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
