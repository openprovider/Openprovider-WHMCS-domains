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
    public function getNameservers(Domain $domain): array
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
    public function saveNameservers(Domain $domain, array $nameServers): array
    {
        $domainOpId = $this->getDomain($domain)['id'];

        $args = [
            'id' => $domainOpId,
            'nameServers' => $nameServers,
        ];

        return $this->apiClient->call('modifyDomainRequest', $args)->getData();
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
}
