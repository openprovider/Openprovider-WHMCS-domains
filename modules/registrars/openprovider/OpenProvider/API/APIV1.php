<?php

namespace OpenProvider\API;

use Openprovider\Api\Rest\Client\Base\Configuration as BaseConfiguration;
use GuzzleHttp6\Client as HttpClient;

class APIV1 implements APIInterface
{
    private $httpClient;
    private $configuration;

    public function __construct()
    {
        $this->configuration = new BaseConfiguration();
        $this->httpClient    = new HttpClient();
    }

    /**
     * @param string $method
     * @param array $args
     */
    public function sendRequest(string $method, $args = [])
    {

    }

    /**
     * @param $httpClient
     */
    public function setHttpClient($httpClient)
    {
        $this->httpClient = $httpClient;
    }

    /**
     * @param $configuration
     */
    public function setConfiguration($configuration)
    {
        $this->configuration = $configuration;
    }
}
