<?php

namespace OpenProvider\API;

use Symfony\Component\Serializer\Normalizer\PropertyNormalizer;
use Symfony\Component\Serializer\Serializer;
use WeDevelopCoffee\wPower\Models\Domain as DomainModel;

class ApiHelper
{
    /**
     * @var ApiInterface
     */
    private $apiClient;
    /**
     * @var Serializer
     */
    private $serializer;

    /**
     * ApiManager constructor.
     * @param ApiInterface $apiClient
     */
    public function __construct(ApiInterface $apiClient)
    {
        $this->apiClient = $apiClient;
        $this->serializer = new Serializer([new PropertyNormalizer()]);
    }

    /**
     * @param Domain $domain
     * @return array
     * @throws \Exception
     */
    public function getDomain(Domain $domain, array $additionalArgs = []): array
    {
        $args = [
            'domainNamePattern'     => $domain->name,
            'extension'             => $domain->extension,
            'withVerificationEmail' => true,
        ];

        $args = array_merge($args, $additionalArgs);
        $domain = $this->buildResponse($this->apiClient->call('searchDomainRequest', $args));

        if (!is_null($domain['results'][0])) {
            return $domain['results'][0];
        }
        
        throw new \Exception('Domain does not exist in Openprovider!');
    }

    /**
     * @param int $id
     * @return string
     * @throws \Exception
     */
    public function getEPPCode(int $id): string
    {
        $args = [
            'id'     => $id,
        ];

        $result = '';

        try {
            $result = $this->buildResponse($this->apiClient->call('getEPPCodeRequest', $args));

            if (isset($result['authCode'])) {
                return $result['authCode'];
            }

            if (isset($result['message']) && $result['message'] != "") {
                throw new \Exception($result['message']);
            }
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage(), $e->getCode());
        }
    }

    /**
     * @param int $id
     * @param array $data
     * @return array
     * @throws \Exception
     */
    public function updateDomain(int $id, array $data): array
    {
        $args = [
            'id' => $id,
        ];
        $args = array_merge($args, $data);

        return $this->buildResponse($this->apiClient->call('modifyDomainRequest', $args));
    }

    /**
     * @param DomainRegistration $domainRegistration
     * @return array
     * @throws \Exception
     */
    public function createDomain(DomainRegistration $domainRegistration): array
    {
        $args = $this->serializer->normalize($domainRegistration);

        $result = $this->buildResponse($this->apiClient->call('createDomainRequest', $args));

        if ($domainRegistration->dnsmanagement) {
            try {
                $zoneResult = $this->getDns($domainRegistration->domain);
            } catch (\Exception $e) {
                $zoneResult = [];
            }

            if (empty($zoneResult)) {
                try {
                    $this->createDnsRecords($domainRegistration->domain, []);
                } catch (\Exception $e) {
                }
            }
        }

        return $result;
    }

    /**
     * @param DomainTransfer $domainTransfer
     * @return array
     * @throws \Exception
     */
    public function transferDomain(DomainTransfer $domainTransfer): array
    {
        $args = $this->serializer->normalize($domainTransfer);

        return $this->buildResponse($this->apiClient->call('transferDomainRequest', $args));
    }

    /**
     * @param int $id
     * @return array
     * @throws \Exception
     */
    public function deleteDomain(int $id): array
    {
        $args = [
            'id' => $id,
        ];

        return $this->buildResponse($this->apiClient->call('deleteDomainRequest', $args));
    }

    /**
     * @param int $id
     * @return array
     * @throws \Exception
     */
    public function restoreDomain(int $id): array
    {
        $args = [
            'id' => $id,
        ];

        return $this->buildResponse($this->apiClient->call('restoreDomainRequest', $args));
    }

    /**
     * @param int $id
     * @param int $period
     * @return array
     * @throws \Exception
     */
    public function renewDomain(int $id, int $period): array
    {
        $args = [
            'id' => $id,
            'period' => $period,
        ];

        return $this->buildResponse($this->apiClient->call('renewDomainRequest', $args));
    }

    /**
     * @param Domain $domain
     * @return array
     * @throws \Exception
     */
    public function getDomainNameservers(Domain $domain): array
    {
        $domainOp = $this->getDomain($domain);
        $nameServers = [];

        foreach ($domainOp['nameServers'] as $ns) {
            $nameServers[] = $ns['name'] ?? $ns['ip'];
        }

        return $nameServers;
    }

    /**
     * @param Domain $domain
     * @param array $nameServers
     * @return array
     * @throws \Exception
     */
    public function saveDomainNameservers(Domain $domain, array $nameServers): array
    {
        $domainOpId = $this->getDomain($domain)['id'];

        $args = [
            'id' => $domainOpId,
            'nameServers' => $nameServers,
        ];

        return $this->buildResponse($this->apiClient->call('modifyDomainRequest', $args));
    }

    /**
     * @param array $domains
     * @return array
     * @throws \Exception
     */
    public function checkDomains(array $domains): array
    {
        $args = [
            'domains' => $domains,
        ];

        return $this->buildResponse($this->apiClient->call('checkDomainRequest', $args));
    }

    /**
     * @param Domain $domain
     * @return array
     * @throws \Exception
     */
    public function getDomainContacts(Domain $domain): array
    {
        $domainOp = $this->getDomain($domain);

        $contacts = [];
        foreach (APIConfig::$handlesNames as $key => $name) {
            if (empty($domainOp[$key])) {
                continue;
            }

            $contacts[$name] = $this->getCustomer($domainOp[$key]);
        }

        unset($contacts['Reseller']);
        unset($contacts['reseller']);

        return $contacts;
    }

    /**
     * @param DomainModel $domainModel
     * @param array $domainOp
     * @return array|string
     * @throws \Exception
     */
    public function toggleAutorenewDomain(DomainModel $domainModel, array $domainOp)
    {
        // Check if we should auto renew or use the default settings
        if ($domainModel->donotrenew == 0)
            $auto_renew = 'default';
        else
            $auto_renew = 'off';

        // Check if openprovider has the same data
        if ($domainModel['autorenew'] != $auto_renew) {
            $args = [
                'autorenew' => $auto_renew,
            ];

            $this->updateDomain($domainOp['id'], $args);

            return [
                'status'      => 'changed',
                'old_setting' => $domainModel['autorenew'],
                'new_setting' => $auto_renew
            ];
        }

        return 'correct';
    }

    /**
     * @param DomainModel $domainModel
     * @param array $domainOp
     * @return array|string
     * @throws \Exception
     */
    public function toggleWhoisProtection(DomainModel $domainModel, array $domainOp)
    {
        $idprotection = $domainModel->idprotection == 1;

        // Check if openprovider has the same data
        if ($domainOp['isPrivateWhoisEnabled'] != $idprotection) {
            if ($idprotection == false) {
                $domainOp['isPrivateWhoisEnabled'] = true;
            }

            if (!is_null($this->apiClient)) {
                $this->apiClient->call('modifyDomainRequest', [
                    'id' => $domainOp['id'],
                    'isPrivateWhoisEnabled' => $idprotection,
                ]);
            } else {
                $args = [
                    'isPrivateWhoisEnabled' => $idprotection,
                ];
                $this->updateDomain($domainOp['id'], $args);
            }

            return [
                'status'      => 'changed',
                'old_setting' => $domainOp['isPrivateWhoisEnabled'],
                'new_setting' => $idprotection
            ];
        }

        return 'correct';
    }

    /**
     * @return array
     */
    public function getNameserverList(string $nameServerName = ""): array
    {
        $nameServers = array();
        $args = [];
        if (empty($nameServerName)) {
            $data = $this->apiClient->call('listNsRequest', $args)->getData();
            $result = $data['results'];
            foreach ($result as $item) {
                if (isset($item['ip']) && isset($item['name'])) {
                    $nameServers[] = new \OpenProvider\API\DomainNameServer(array(
                        'name'  =>  $item['name'],
                        'ip'    =>  $item['ip']
                    ));
                }
            }
        } else {
            $args = [
                'name' => $nameServerName,
            ];
            $data = $this->apiClient->call('searchNsRequest', $args)->getData();
            $nameServers[] = new \OpenProvider\API\DomainNameServer(array(
                'name'  =>  $data['name'],
                'ip'    =>  $data['ip']
            ));
        }

        return $nameServers;
    }

    /**
     * @param DomainNameServer $nameServer
     * @return array
     * @throws \Exception
     */
    public function createNameserver(DomainNameServer $nameServer): array
    {
        $args = $this->serializer->normalize($nameServer);

        return $this->buildResponse($this->apiClient->call('createNsRequest', $args));
    }

    /**
     * @param DomainNameServer $nameServer
     * @param string $currentIp
     * @return array
     * @throws \Exception
     */
    public function updateNameserver(DomainNameServer $nameServer, string $currentIp): array
    {
        $args = [
            'name' => $nameServer->name,
        ];
        $nameServerOp = $this->apiClient->call('searchNsRequest', $args)->getData();

        if ($nameServerOp['ip'] != $currentIp) {
            throw new \Exception('Current IP Address is incorrect');
        }

        $args = $this->serializer->normalize($nameServer);

        return $this->buildResponse($this->apiClient->call('modifyNsRequest', $args));
    }

    /**
     * @param string $nameServerName
     * @return array
     * @throws \Exception
     */
    public function deleteNameserver(string $nameServerName): array
    {
        $args = [
            'name' => $nameServerName,
        ];

        return $this->buildResponse($this->apiClient->call('deleteNsRequest', $args));
    }

    /**
     * @param Domain $domain
     * @return array
     * @throws \Exception
     */
    public function getDns(Domain $domain): array
    {
        $args = [
            'name' => $domain->getFullName(),
            'withHistory' => false,
        ];

        return $this->buildResponse($this->apiClient->call('retrieveZoneDnsRequest', $args));
    }

    /**
     * @param Domain $domain
     * @param array $records
     * @return array
     * @throws \Exception
     */
    public function updateDnsRecords(Domain $domain, array $records): array
    {
        $args = [
            'name' => $domain->getFullName(),
            'type' => 'master',
            'records' => [
                'replace' => $records,
            ]
        ];

        return $this->buildResponse($this->apiClient->call('modifyZoneDnsRequest', $args));
    }

    /**
     * @param Domain $domain
     * @param $records
     * @return array
     * @throws \Exception
     */
    public function createDnsRecords(Domain $domain, $records): array
    {
        $args = [
            'domain' => $this->serializer->normalize($domain),
            'type' => 'master',
            'records' => $records,
        ];

        return $this->buildResponse($this->apiClient->call('createZoneDnsRequest', $args));
    }

    /**
     * @param Domain $domain
     * @return array
     * @throws \Exception
     */
    public function deleteDnsRecords(Domain $domain): array
    {
        $args = [
            'name' => $domain->getFullName(),
        ];

        try {
            $this->apiClient->call('deleteZoneDnsRequest', $args);
        } catch (\Exception $e) {
            if (
                $e->getCode() != 872 ||
                strpos('Zone specified is not found', $e->getMessage()) === false
            ) {
                throw new \Exception($e->getMessage(), $e->getCode());
            }
        }

        return [];
    }

    /**
     * @param string $handle
     * @param bool $formattedForWhmcs
     * @return array
     * @throws \Exception
     */
    public function getCustomer(string $handle, bool $formattedForWhmcs = true): array
    {
        $args = [
            'handle' => $handle,
            'with_additional_data' => 1
        ];

        $customerOp = $this->buildResponse($this->apiClient->call('retrieveCustomerRequest', $args));

        if (!$formattedForWhmcs) {
            return $customerOp;
        }

        $customerInfo = [];
        $customerInfo['First Name'] = $customerOp['name']['firstName'];
        $customerInfo['Last Name'] = $customerOp['name']['lastName'];
        $customerInfo['Company Name'] = $customerOp['companyName'];
        $customerInfo['Email Address'] = $customerOp['email'];
        if ($customerOp['address']['country'] === 'US') {
            $customerInfo['Address'] =
                $customerOp['address']['number'] . ' ' .
                $customerOp['address']['street'] . ' ' .
                $customerOp['address']['suffix'];
        } else {
            $customerInfo['Address'] =
                $customerOp['address']['street'] . ' ' .
                $customerOp['address']['number'] . ' ' .
                $customerOp['address']['suffix'];
        }        
        $customerInfo['City'] = $customerOp['address']['city'];
        $customerInfo['State'] = $customerOp['address']['state'];
        $customerInfo['Zip Code'] = $customerOp['address']['zipcode'];
        $customerInfo['Country'] = $customerOp['address']['country'];
        $customerInfo['Phone Number'] = $customerOp['phone']['countryCode'] . '.' .
            $customerOp['phone']['areaCode'] .
            $customerOp['phone']['subscriberNumber'];


        if (!empty($customerOp['companyName'])) {
            if (empty($customerOp['additionalData']['companyRegistrationNumber'])) {
                $customerInfo['Vat or Tax ID'] = $customerOp['vat'];
            } else {
                $customerInfo['Company or Individual Id'] = $customerOp['additionalData']['companyRegistrationNumber'];
            }
        } else {
            if (empty($customerOp['additionalData']['passportNumber'])) {
                $customerInfo['Vat or Tax ID'] = $customerOp['additionalData']['socialSecurityNumber'];
            } else {
                $customerInfo['Company or Individual Id'] = $customerOp['additionalData']['passportNumber'];
            }
        }

        return $customerInfo;
    }

    /**
     * @param Customer $customer
     * @return array
     * @throws \Exception
     */
    public function createCustomer(Customer $customer): array
    {
        $args = $this->serializer->normalize($customer);
        return $this->buildResponse($this->apiClient->call('createCustomerRequest', $args));
    }

    /**
     * @param string $handle
     * @param Customer $customer
     * @return array
     * @throws \Exception
     */
    public function updateCustomer(string $handle, Customer $customer): array
    {
        $oldCustomer = $this->getCustomer($handle, false);
        $args = $this->serializer->normalize($customer);
        $args['handle'] = $handle;
        $args = $this->removeEmptyElementsFromArray($args);

        $mergedArgs = array_merge($oldCustomer, $args);

        return $this->buildResponse($this->apiClient->call('modifyCustomerRequest', $mergedArgs));
    }

    /**
     * @return array
     * @throws \Exception
     */
    public function getReseller(): array
    {
        $args = [
            'withStatistics' => true,
        ];

        return $this->buildResponse($this->apiClient->call('retrieveResellerRequest', $args));
    }

    /**
     * @return array
     *
     * @throws \Exception
     */
    public function getPromoMessages(): array
    {
        return $this->buildResponse($this->apiClient->call('searchPromoMessageRequest'));
    }

    /**
     * @param ResponseInterface $response
     * @return array
     * @throws \Exception
     */
    private function buildResponse(ResponseInterface $response): array
    {
        if (!$response->isSuccess()) {
            throw new \Exception($response->getMessage(), $response->getCode());
        }

        return $response->getData();
    }

    private function removeEmptyElementsFromArray(array $arr): array
    {
        foreach ($arr as $key => $value) {
            if (is_array($value)) {
                $arr[$key] = $this->removeEmptyElementsFromArray($value);
            }

            if (empty($arr[$key])) {
                unset($arr[$key]);
            }
        }

        return $arr;
    }
}
