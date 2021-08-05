<?php

/**
 * This file is auto-generated.
 */
namespace Openprovider\Api\Rest\Client\Billing;

use GuzzleHttp6\ClientInterface;
use Openprovider\Api\Rest\Client\Base\Configuration;
use Openprovider\Api\Rest\Client\Base\HeaderSelector;
use Openprovider\Api\Rest\Client\Billing\Api\InvoiceServiceApi;
use Openprovider\Api\Rest\Client\Billing\Api\PaymentApi;
use Openprovider\Api\Rest\Client\Billing\Api\TransactionApi;

class BillingModule 
{
    /** @var InvoiceServiceApi */
    protected $InvoiceServiceApi;

    /** @var PaymentApi */
    protected $PaymentApi;

    /** @var TransactionApi */
    protected $TransactionApi;

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
        $this->InvoiceServiceApi = new InvoiceServiceApi($client, $config, $selector, $host_index);
	    $this->PaymentApi = new PaymentApi($client, $config, $selector, $host_index);
	    $this->TransactionApi = new TransactionApi($client, $config, $selector, $host_index);
    }

    /**
     * Gets InvoiceServiceApi api.
     * @return InvoiceServiceApi
     */
    public function getInvoiceServiceApi() 
    {
      return $this->InvoiceServiceApi;
    }

    /**
     * Gets PaymentApi api.
     * @return PaymentApi
     */
    public function getPaymentApi() 
    {
      return $this->PaymentApi;
    }

    /**
     * Gets TransactionApi api.
     * @return TransactionApi
     */
    public function getTransactionApi() 
    {
      return $this->TransactionApi;
    }
}
