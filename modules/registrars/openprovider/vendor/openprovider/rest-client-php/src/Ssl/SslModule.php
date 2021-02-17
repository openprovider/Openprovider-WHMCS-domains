<?php

/**
 * This file is auto-generated.
 */
namespace Openprovider\Api\Rest\Client\Ssl;

use GuzzleHttp6\ClientInterface;
use Openprovider\Api\Rest\Client\Base\Configuration;
use Openprovider\Api\Rest\Client\Base\HeaderSelector;
use Openprovider\Api\Rest\Client\Ssl\Api\ApproverEmailApi;
use Openprovider\Api\Rest\Client\Ssl\Api\CsrApi;
use Openprovider\Api\Rest\Client\Ssl\Api\OrderApi;
use Openprovider\Api\Rest\Client\Ssl\Api\OrderApproverEmailApi;
use Openprovider\Api\Rest\Client\Ssl\Api\OtpTokenApi;
use Openprovider\Api\Rest\Client\Ssl\Api\ProductApi;

class SslModule 
{
    /** @var ApproverEmailApi */
    protected $ApproverEmailApi;

    /** @var CsrApi */
    protected $CsrApi;

    /** @var OrderApi */
    protected $OrderApi;

    /** @var OrderApproverEmailApi */
    protected $OrderApproverEmailApi;

    /** @var OtpTokenApi */
    protected $OtpTokenApi;

    /** @var ProductApi */
    protected $ProductApi;

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
        $this->ApproverEmailApi = new ApproverEmailApi($client, $config, $selector, $host_index);
	    $this->CsrApi = new CsrApi($client, $config, $selector, $host_index);
	    $this->OrderApi = new OrderApi($client, $config, $selector, $host_index);
	    $this->OrderApproverEmailApi = new OrderApproverEmailApi($client, $config, $selector, $host_index);
	    $this->OtpTokenApi = new OtpTokenApi($client, $config, $selector, $host_index);
	    $this->ProductApi = new ProductApi($client, $config, $selector, $host_index);
    }

    /**
     * Gets ApproverEmailApi api.
     * @return ApproverEmailApi
     */
    public function getApproverEmailApi() 
    {
      return $this->ApproverEmailApi;
    }

    /**
     * Gets CsrApi api.
     * @return CsrApi
     */
    public function getCsrApi() 
    {
      return $this->CsrApi;
    }

    /**
     * Gets OrderApi api.
     * @return OrderApi
     */
    public function getOrderApi() 
    {
      return $this->OrderApi;
    }

    /**
     * Gets OrderApproverEmailApi api.
     * @return OrderApproverEmailApi
     */
    public function getOrderApproverEmailApi() 
    {
      return $this->OrderApproverEmailApi;
    }

    /**
     * Gets OtpTokenApi api.
     * @return OtpTokenApi
     */
    public function getOtpTokenApi() 
    {
      return $this->OtpTokenApi;
    }

    /**
     * Gets ProductApi api.
     * @return ProductApi
     */
    public function getProductApi() 
    {
      return $this->ProductApi;
    }
}
