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
}
