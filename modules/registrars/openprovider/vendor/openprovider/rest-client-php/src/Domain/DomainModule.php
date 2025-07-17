<?php

/**
 * This file is auto-generated.
 */
namespace Openprovider\Api\Rest\Client\Domain;

use GuzzleHttp6\ClientInterface;
use Openprovider\Api\Rest\Client\Base\Configuration;
use Openprovider\Api\Rest\Client\Base\HeaderSelector;
use Openprovider\Api\Rest\Client\Domain\Api\AdditionalDataApi;
use Openprovider\Api\Rest\Client\Domain\Api\AuthCodeApi;
use Openprovider\Api\Rest\Client\Domain\Api\CustomerAdditionalDataApi;
use Openprovider\Api\Rest\Client\Domain\Api\DomainPriceServiceApi;
use Openprovider\Api\Rest\Client\Domain\Api\DomainServiceApi;

class DomainModule 
{
    /** @var AdditionalDataApi */
    protected $AdditionalDataApi;

    /** @var AuthCodeApi */
    protected $AuthCodeApi;

    /** @var CustomerAdditionalDataApi */
    protected $CustomerAdditionalDataApi;

    /** @var DomainPriceServiceApi */
    protected $DomainPriceServiceApi;

    /** @var DomainServiceApi */
    protected $DomainServiceApi;

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
        $this->AdditionalDataApi = new AdditionalDataApi($client, $config, $selector, $host_index);
	    $this->AuthCodeApi = new AuthCodeApi($client, $config, $selector, $host_index);
	    $this->CustomerAdditionalDataApi = new CustomerAdditionalDataApi($client, $config, $selector, $host_index);
	    $this->DomainPriceServiceApi = new DomainPriceServiceApi($client, $config, $selector, $host_index);
	    $this->DomainServiceApi = new DomainServiceApi($client, $config, $selector, $host_index);
    }

    /**
     * Gets AdditionalDataApi api.
     * @return AdditionalDataApi
     */
    public function getAdditionalDataApi() 
    {
      return $this->AdditionalDataApi;
    }

    /**
     * Gets AuthCodeApi api.
     * @return AuthCodeApi
     */
    public function getAuthCodeApi() 
    {
      return $this->AuthCodeApi;
    }

    /**
     * Gets CustomerAdditionalDataApi api.
     * @return CustomerAdditionalDataApi
     */
    public function getCustomerAdditionalDataApi() 
    {
      return $this->CustomerAdditionalDataApi;
    }

    /**
     * Gets DomainPriceServiceApi api.
     * @return DomainPriceServiceApi
     */
    public function getDomainPriceServiceApi() 
    {
      return $this->DomainPriceServiceApi;
    }

    /**
     * Gets DomainServiceApi api.
     * @return DomainServiceApi
     */
    public function getDomainServiceApi() 
    {
      return $this->DomainServiceApi;
    }
}
