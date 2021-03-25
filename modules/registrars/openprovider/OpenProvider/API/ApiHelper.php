<?php

namespace OpenProvider\API;

class ApiHelper
{
    /**
     * @var ApiInterface
     */
    private $apiClient;

    /**
     * ApiManager constructor.
     * @param ApiInterface $apiClient
     */
    public function __construct(ApiInterface $apiClient)
    {
        $this->apiClient = $apiClient;
    }

    /**
     * @param Domain $domain
     * @return array
     */
    public function getDomain(Domain $domain): array
    {
        $args = [
            'domainNamePattern' => $domain->name,
            'extension' => $domain->extension,
        ];

        return $this->apiClient->call('searchDomainRequest', $args)->getData()['results'][0] ?? [];
    }

    /**
     * @param int $id
     * @param array $data
     * @return array
     */
    public function updateDomain(int $id, array $data): array
    {
        $args = [
            'id' => $id,
        ];
        $args = array_merge($args, $data);

        return $this->apiClient->call('modifyDomainRequest', $args)->getData();
    }

    /**
     * @param Domain $domain
     * @return array
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
     */
    public function saveDomainNameservers(Domain $domain, array $nameServers): array
    {
        $domainOpId = $this->getDomain($domain)['id'];

        $args = [
            'id' => $domainOpId,
            'nameServers' => $nameServers,
        ];

        return $this->apiClient->call('modifyDomainRequest', $args)->getData();
    }

    /**
     * @param DomainNameServer $nameServer
     * @return array
     */
    public function createNameserver(DomainNameServer $nameServer): array
    {
        $args = [
            'name' => $nameServer->name,
            'ip' => $nameServer->ip,
        ];

        return $this->apiClient->call('createNsRequest', $args)->getData();
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
        $nameServerOp = $this->apiClient->call('retrieveNsRequest', $args)->getData();

        if ($nameServerOp['ip'] != $currentIp) {
            throw new \Exception('Current IP Address is incorrect');
        }

        $args = [
            'name' => $nameServer->name,
            'ip' => $nameServer->ip,
        ];

        return $this->apiClient->call('modifyNsRequest', $args)->getData();
    }

    public function deleteNameserver(string $nameServerName): array
    {
        $args = [
            'name' => $nameServerName,
        ];

        return $this->apiClient->call('deleteNsRequest', $args)->getData();
    }

    /**
     * @param Domain $domain
     * @return array
     */
    public function getDns(Domain $domain): array
    {
        $args = [
            'name' => $domain->getFullName(),
            'withHistory' => false,
        ];

        return $this->apiClient->call('retrieveZoneDnsRequest', $args)->getData();
    }

    /**
     * @param Domain $domain
     * @param array $prevRecords
     * @param array $newRecords
     * @return array
     */
    public function updateDnsRecords(Domain $domain, array $prevRecords, array $newRecords): array
    {
        $args = [
            'name' => $domain->getFullName(),
            'type' => 'master',
            'records' => [
                'remove' => $prevRecords,
                'add' => $newRecords,
            ]
        ];

        return $this->apiClient->call('modifyZoneDnsRequest', $args)->getData();
    }

    /**
     * @param Domain $domain
     * @param $records
     * @return array
     */
    public function createDnsRecords(Domain $domain, $records): array
    {
        $args = [
            'name' => $domain->getFullName(),
            'type' => 'master',
            'records' => $records,
        ];

        return $this->apiClient->call('createZoneDnsRequest', $args)->getData();
    }

    /**
     * @param Domain $domain
     * @return array
     */
    public function deleteDnsRecords(Domain $domain): array
    {
        $args = [
            'name' => $domain->getFullName(),
        ];

        return $this->apiClient->call('deleteZoneDnsRequest', $args)->getData();
    }

    public function getCustomer($handle, bool $formattedForWhmcs = true): array
    {
        $args = [
            'handle' => $handle,
        ];

        $customerOp = $this->apiClient->call('retrieveCustomerRequest', $args)->getData();

        if (!$formattedForWhmcs) {
            return $customerOp;
        }

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

        return $customerInfo;
    }
}
