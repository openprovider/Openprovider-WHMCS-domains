<?php

/**
 * This file is auto-generated.
 */
namespace Openprovider\Api\Rest\Client\Helpers;

use GuzzleHttp6\ClientInterface;
use Openprovider\Api\Rest\Client\Base\Configuration;
use Openprovider\Api\Rest\Client\Base\HeaderSelector;
use Openprovider\Api\Rest\Client\Helpers\Api\TagServiceApi;

class HelpersModule 
{
    /** @var TagServiceApi */
    protected $TagServiceApi;

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
        $this->TagServiceApi = new TagServiceApi($client, $config, $selector, $host_index);
    }

    /**
     * Gets TagServiceApi api.
     * @return TagServiceApi
     */
    public function getTagServiceApi() 
    {
      return $this->TagServiceApi;
    }
}
