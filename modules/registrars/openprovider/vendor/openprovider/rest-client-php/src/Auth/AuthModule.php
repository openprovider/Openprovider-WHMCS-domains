<?php

/**
 * This file is auto-generated.
 */
namespace Openprovider\Api\Rest\Client\Auth;

use GuzzleHttp6\ClientInterface;
use Openprovider\Api\Rest\Client\Base\Configuration;
use Openprovider\Api\Rest\Client\Base\HeaderSelector;
use Openprovider\Api\Rest\Client\Auth\Api\AuthApi;
use Openprovider\Api\Rest\Client\Auth\Api\SpamExpertApi;

class AuthModule 
{
    /** @var AuthApi */
    protected $AuthApi;

    /** @var SpamExpertApi */
    protected $SpamExpertApi;

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
        $this->AuthApi = new AuthApi($client, $config, $selector, $host_index);
	    $this->SpamExpertApi = new SpamExpertApi($client, $config, $selector, $host_index);
    }

    /**
     * Gets AuthApi api.
     * @return AuthApi
     */
    public function getAuthApi() 
    {
      return $this->AuthApi;
    }

    /**
     * Gets SpamExpertApi api.
     * @return SpamExpertApi
     */
    public function getSpamExpertApi() 
    {
      return $this->SpamExpertApi;
    }
}
