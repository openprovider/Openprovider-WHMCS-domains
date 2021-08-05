<?php

/**
 * This file is auto-generated.
 */
namespace Openprovider\Api\Rest\Client\Person;

use GuzzleHttp6\ClientInterface;
use Openprovider\Api\Rest\Client\Base\Configuration;
use Openprovider\Api\Rest\Client\Base\HeaderSelector;
use Openprovider\Api\Rest\Client\Person\Api\ContactServiceApi;
use Openprovider\Api\Rest\Client\Person\Api\CustomerApi;
use Openprovider\Api\Rest\Client\Person\Api\EmailVerificationApi;
use Openprovider\Api\Rest\Client\Person\Api\PromoMessageServiceApi;
use Openprovider\Api\Rest\Client\Person\Api\ResellerServiceApi;
use Openprovider\Api\Rest\Client\Person\Api\SettingsApi;
use Openprovider\Api\Rest\Client\Person\Api\StatisticsApi;

class PersonModule 
{
    /** @var ContactServiceApi */
    protected $ContactServiceApi;

    /** @var CustomerApi */
    protected $CustomerApi;

    /** @var EmailVerificationApi */
    protected $EmailVerificationApi;

    /** @var PromoMessageServiceApi */
    protected $PromoMessageServiceApi;

    /** @var ResellerServiceApi */
    protected $ResellerServiceApi;

    /** @var SettingsApi */
    protected $SettingsApi;

    /** @var StatisticsApi */
    protected $StatisticsApi;

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
        $this->ContactServiceApi = new ContactServiceApi($client, $config, $selector, $host_index);
	    $this->CustomerApi = new CustomerApi($client, $config, $selector, $host_index);
	    $this->EmailVerificationApi = new EmailVerificationApi($client, $config, $selector, $host_index);
	    $this->PromoMessageServiceApi = new PromoMessageServiceApi($client, $config, $selector, $host_index);
	    $this->ResellerServiceApi = new ResellerServiceApi($client, $config, $selector, $host_index);
	    $this->SettingsApi = new SettingsApi($client, $config, $selector, $host_index);
	    $this->StatisticsApi = new StatisticsApi($client, $config, $selector, $host_index);
    }

    /**
     * Gets ContactServiceApi api.
     * @return ContactServiceApi
     */
    public function getContactServiceApi() 
    {
      return $this->ContactServiceApi;
    }

    /**
     * Gets CustomerApi api.
     * @return CustomerApi
     */
    public function getCustomerApi() 
    {
      return $this->CustomerApi;
    }

    /**
     * Gets EmailVerificationApi api.
     * @return EmailVerificationApi
     */
    public function getEmailVerificationApi() 
    {
      return $this->EmailVerificationApi;
    }

    /**
     * Gets PromoMessageServiceApi api.
     * @return PromoMessageServiceApi
     */
    public function getPromoMessageServiceApi() 
    {
      return $this->PromoMessageServiceApi;
    }

    /**
     * Gets ResellerServiceApi api.
     * @return ResellerServiceApi
     */
    public function getResellerServiceApi() 
    {
      return $this->ResellerServiceApi;
    }

    /**
     * Gets SettingsApi api.
     * @return SettingsApi
     */
    public function getSettingsApi() 
    {
      return $this->SettingsApi;
    }

    /**
     * Gets StatisticsApi api.
     * @return StatisticsApi
     */
    public function getStatisticsApi() 
    {
      return $this->StatisticsApi;
    }
}
