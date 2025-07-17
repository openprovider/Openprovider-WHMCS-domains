<?php

/**
 * This file is auto-generated.
 */
namespace Openprovider\Api\Rest\Client\Dns;

use GuzzleHttp6\ClientInterface;
use Openprovider\Api\Rest\Client\Base\Configuration;
use Openprovider\Api\Rest\Client\Base\HeaderSelector;
use Openprovider\Api\Rest\Client\Dns\Api\NameserverServiceApi;
use Openprovider\Api\Rest\Client\Dns\Api\NsGroupServiceApi;
use Openprovider\Api\Rest\Client\Dns\Api\TemplateServiceApi;
use Openprovider\Api\Rest\Client\Dns\Api\ZoneRecordServiceApi;
use Openprovider\Api\Rest\Client\Dns\Api\ZoneServiceApi;

class DnsModule 
{
    /** @var NameserverServiceApi */
    protected $NameserverServiceApi;

    /** @var NsGroupServiceApi */
    protected $NsGroupServiceApi;

    /** @var TemplateServiceApi */
    protected $TemplateServiceApi;

    /** @var ZoneRecordServiceApi */
    protected $ZoneRecordServiceApi;

    /** @var ZoneServiceApi */
    protected $ZoneServiceApi;

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
        $this->NameserverServiceApi = new NameserverServiceApi($client, $config, $selector, $host_index);
	    $this->NsGroupServiceApi = new NsGroupServiceApi($client, $config, $selector, $host_index);
	    $this->TemplateServiceApi = new TemplateServiceApi($client, $config, $selector, $host_index);
	    $this->ZoneRecordServiceApi = new ZoneRecordServiceApi($client, $config, $selector, $host_index);
	    $this->ZoneServiceApi = new ZoneServiceApi($client, $config, $selector, $host_index);
    }

    /**
     * Gets NameserverServiceApi api.
     * @return NameserverServiceApi
     */
    public function getNameserverServiceApi() 
    {
      return $this->NameserverServiceApi;
    }

    /**
     * Gets NsGroupServiceApi api.
     * @return NsGroupServiceApi
     */
    public function getNsGroupServiceApi() 
    {
      return $this->NsGroupServiceApi;
    }

    /**
     * Gets TemplateServiceApi api.
     * @return TemplateServiceApi
     */
    public function getTemplateServiceApi() 
    {
      return $this->TemplateServiceApi;
    }

    /**
     * Gets ZoneRecordServiceApi api.
     * @return ZoneRecordServiceApi
     */
    public function getZoneRecordServiceApi() 
    {
      return $this->ZoneRecordServiceApi;
    }

    /**
     * Gets ZoneServiceApi api.
     * @return ZoneServiceApi
     */
    public function getZoneServiceApi() 
    {
      return $this->ZoneServiceApi;
    }
}
