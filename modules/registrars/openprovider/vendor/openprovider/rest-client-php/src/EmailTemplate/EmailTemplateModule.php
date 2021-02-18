<?php

/**
 * This file is auto-generated.
 */
namespace Openprovider\Api\Rest\Client\Template;

use GuzzleHttp6\ClientInterface;
use Openprovider\Api\Rest\Client\Base\Configuration;
use Openprovider\Api\Rest\Client\Base\HeaderSelector;
use Openprovider\Api\Rest\Client\Template\Api\EmailApi;

class EmailTemplateModule 
{
    /** @var EmailApi */
    protected $EmailApi;

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
        $this->EmailApi = new EmailApi($client, $config, $selector, $host_index);
    }

    /**
     * Gets EmailApi api.
     * @return EmailApi
     */
    public function getEmailApi() 
    {
      return $this->EmailApi;
    }
}
