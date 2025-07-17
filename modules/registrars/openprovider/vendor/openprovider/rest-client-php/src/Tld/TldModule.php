<?php

/**
 * This file is auto-generated.
 */
namespace Openprovider\Api\Rest\Client\Tld;

use GuzzleHttp6\ClientInterface;
use Openprovider\Api\Rest\Client\Base\Configuration;
use Openprovider\Api\Rest\Client\Base\HeaderSelector;
use Openprovider\Api\Rest\Client\Tld\Api\TldServiceApi;

class TldModule 
{
    /** @var TldServiceApi */
    protected $TldServiceApi;

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
        $this->TldServiceApi = new TldServiceApi($client, $config, $selector, $host_index);
    }

    /**
     * Gets TldServiceApi api.
     * @return TldServiceApi
     */
    public function getTldServiceApi() 
    {
      return $this->TldServiceApi;
    }
}
