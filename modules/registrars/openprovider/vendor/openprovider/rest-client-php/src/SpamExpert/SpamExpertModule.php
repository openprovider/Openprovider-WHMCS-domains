<?php

/**
 * This file is auto-generated.
 */
namespace Openprovider\Api\Rest\Client\SpamExpert;

use GuzzleHttp6\ClientInterface;
use Openprovider\Api\Rest\Client\Base\Configuration;
use Openprovider\Api\Rest\Client\Base\HeaderSelector;
use Openprovider\Api\Rest\Client\SpamExpert\Api\SEDomainApi;

class SpamExpertModule 
{
    /** @var SEDomainApi */
    protected $SEDomainApi;

    /**
     * @param ClientInterface $client
     * @param Configuration   $config
     * @param HeaderSelector  $selector
     * @param int             $host_index (Optional) host index to select the list of hosts if defined in the OpenAPI spec
     */
    public function __construct(
        ClientInterface $client = null,
        Configuration $config = null,
        HeaderSelector $selector = null,
        $host_index = 0
    ) {
        $this->SEDomainApi = new SEDomainApi($client, $config, $selector, $host_index);
    }

    /**
     * Gets SEDomainApi api.
     * @return SEDomainApi
     */
    public function getSEDomainApi() 
    {
      return $this->SEDomainApi;
    }
}
