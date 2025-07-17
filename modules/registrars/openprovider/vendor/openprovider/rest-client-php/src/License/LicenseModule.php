<?php

/**
 * This file is auto-generated.
 */
namespace Openprovider\Api\Rest\Client\License;

use GuzzleHttp6\ClientInterface;
use Openprovider\Api\Rest\Client\Base\Configuration;
use Openprovider\Api\Rest\Client\Base\HeaderSelector;
use Openprovider\Api\Rest\Client\License\Api\LicenseServiceApi;

class LicenseModule 
{
    /** @var LicenseServiceApi */
    protected $LicenseServiceApi;

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
        $this->LicenseServiceApi = new LicenseServiceApi($client, $config, $selector, $host_index);
    }

    /**
     * Gets LicenseServiceApi api.
     * @return LicenseServiceApi
     */
    public function getLicenseServiceApi() 
    {
      return $this->LicenseServiceApi;
    }
}
