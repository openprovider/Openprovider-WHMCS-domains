<?php

/**
 * This file is auto-generated.
 */
namespace Openprovider\Api\Rest\Client;

use GuzzleHttp6\ClientInterface;
use Openprovider\Api\Rest\Client\Base\Configuration;
use Openprovider\Api\Rest\Client\Base\HeaderSelector;
use Openprovider\Api\Rest\Client\Billing\BillingModule;
use Openprovider\Api\Rest\Client\Dns\DnsModule;
use Openprovider\Api\Rest\Client\Domain\DomainModule;
use Openprovider\Api\Rest\Client\Tld\TldModule;
use Openprovider\Api\Rest\Client\Template\EmailTemplateModule;
use Openprovider\Api\Rest\Client\Helpers\HelpersModule;
use Openprovider\Api\Rest\Client\License\LicenseModule;
use Openprovider\Api\Rest\Client\Person\PersonModule;
use Openprovider\Api\Rest\Client\Ssl\SslModule;
use Openprovider\Api\Rest\Client\SpamExpert\SpamExpertModule;
use Openprovider\Api\Rest\Client\Auth\AuthModule;

class Client 
{
    /** @var BillingModule */
    protected $BillingModule;

    /** @var DnsModule */
    protected $DnsModule;

    /** @var DomainModule */
    protected $DomainModule;

    /** @var TldModule */
    protected $TldModule;

    /** @var EmailTemplateModule */
    protected $EmailTemplateModule;

    /** @var HelpersModule */
    protected $HelpersModule;

    /** @var LicenseModule */
    protected $LicenseModule;

    /** @var PersonModule */
    protected $PersonModule;

    /** @var SslModule */
    protected $SslModule;

    /** @var SpamExpertModule */
    protected $SpamExpertModule;

    /** @var AuthModule */
    protected $AuthModule;

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
        $this->BillingModule = new BillingModule($client, $config, $selector, $host_index);
	    $this->DnsModule = new DnsModule($client, $config, $selector, $host_index);
	    $this->DomainModule = new DomainModule($client, $config, $selector, $host_index);
	    $this->TldModule = new TldModule($client, $config, $selector, $host_index);
	    $this->EmailTemplateModule = new EmailTemplateModule($client, $config, $selector, $host_index);
	    $this->HelpersModule = new HelpersModule($client, $config, $selector, $host_index);
	    $this->LicenseModule = new LicenseModule($client, $config, $selector, $host_index);
	    $this->PersonModule = new PersonModule($client, $config, $selector, $host_index);
	    $this->SslModule = new SslModule($client, $config, $selector, $host_index);
	    $this->SpamExpertModule = new SpamExpertModule($client, $config, $selector, $host_index);
	    $this->AuthModule = new AuthModule($client, $config, $selector, $host_index);
    }

    /**
     * Gets BillingModule module.
     * @return BillingModule
     */
    public function getBillingModule() 
    {
      return $this->BillingModule;
    }

    /**
     * Gets DnsModule module.
     * @return DnsModule
     */
    public function getDnsModule() 
    {
      return $this->DnsModule;
    }

    /**
     * Gets DomainModule module.
     * @return DomainModule
     */
    public function getDomainModule() 
    {
      return $this->DomainModule;
    }

    /**
     * Gets TldModule module.
     * @return TldModule
     */
    public function getTldModule() 
    {
      return $this->TldModule;
    }

    /**
     * Gets EmailTemplateModule module.
     * @return EmailTemplateModule
     */
    public function getEmailTemplateModule() 
    {
      return $this->EmailTemplateModule;
    }

    /**
     * Gets HelpersModule module.
     * @return HelpersModule
     */
    public function getHelpersModule() 
    {
      return $this->HelpersModule;
    }

    /**
     * Gets LicenseModule module.
     * @return LicenseModule
     */
    public function getLicenseModule() 
    {
      return $this->LicenseModule;
    }

    /**
     * Gets PersonModule module.
     * @return PersonModule
     */
    public function getPersonModule() 
    {
      return $this->PersonModule;
    }

    /**
     * Gets SslModule module.
     * @return SslModule
     */
    public function getSslModule() 
    {
      return $this->SslModule;
    }

    /**
     * Gets SpamExpertModule module.
     * @return SpamExpertModule
     */
    public function getSpamExpertModule() 
    {
      return $this->SpamExpertModule;
    }

    /**
     * Gets AuthModule module.
     * @return AuthModule
     */
    public function getAuthModule() 
    {
      return $this->AuthModule;
    }
}
