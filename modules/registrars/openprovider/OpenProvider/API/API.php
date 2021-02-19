<?php

namespace OpenProvider\API;

use Openprovider\Api\Rest\Client\Auth\Model\AuthLoginRequest;
use Openprovider\Api\Rest\Client\Client;
use Openprovider\Api\Rest\Client\Helpers\Api\TagServiceApi;
use Openprovider\Api\Rest\Client\Base\Configuration as RestConfiguration;

use OpenProvider\WhmcsRegistrar\src\Configuration;

use GuzzleHttp6\Client as HttpClient;

require_once __DIR__.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'classes'.DIRECTORY_SEPARATOR.'idna_convert.class.php';

/**
 * API
 * OpenProvider Registrar module
 *
 * @copyright Copyright (c) Openprovider 2018
 */
class API
{

    protected $api;
    protected $request;
    protected $url;
    protected $error            =   null;
    protected $timeout          =   null;
    protected $debug            =   null;
    protected $username         =   null;
    protected $password         =   null;
    protected $cache; // Cache responses made in this request.

    protected $httpClient;
    protected $configuration;

    // services
    protected $tagService = null;

    /**
     * API constructor.
     */
    public function __construct()
    {
        $this->timeout = APIConfig::$curlTimeout;
        $this->request = new Request();

        $this->httpClient = new HttpClient();
        $this->configuration = new RestConfiguration();
    }

    /**
     * @param $params
     * @param int $debug
     * @throws \Openprovider\Api\Rest\Client\Base\ApiException
     */
    public function setParams($params, $debug = 0)
    {
        session_start();

        if(isset($params['test_mode']) && $params['test_mode'] == 'on')
            $this->url = Configuration::get('api_url_cte');
        else
            $this->url = Configuration::get('api_url');

        $this->configuration->setHost($this->url);

        $this->request->setAuth(array(
            'username' => $params["Username"],
            'password' => $params["Password"],
        ));

        $this->username     =   $params['Username'];
        $this->password     =   $params['Password'];

        $this->debug        =   $debug;

        $tokenNameHash = md5("$this->url-{$params['Username']}-{$params['Password']}");
        $sessionTokenVariable = "token-$tokenNameHash";

        if (!isset($_SESSION[$sessionTokenVariable]) || empty($_SESSION[$sessionTokenVariable])) {
            $client = new Client($this->httpClient, $this->configuration);
            $reply  = $client->getAuthModule()->getAuthApi()->login(
                new AuthLoginRequest([
                    'username' => $this->username,
                    'password' => $this->password
                ])
            );

            $_SESSION[$sessionTokenVariable] = $reply->getData()->getToken();
        }
        $this->configuration->setAccessToken($_SESSION[$sessionTokenVariable]);

        $this->initServices();
    }

    private function initServices()
    {
        $this->tagService = new TagServiceApi($this->httpClient, $this->configuration);
    }

    public function modifyCustomer(Customer $customer)
    {
        $args = $customer;
        $this->sendRequest('modifyCustomerRequest', $args);
    }

    public function createCustomerInOPdatabase(Customer $customer)
    {
        $args = $customer;
        return $this->sendRequest('createCustomerRequest', $args);
    }

    public function sendRequest($requestCommand, $args = null)
    {
        // prepare request
        $this->request->setCommand($requestCommand);

        $this->request->setArgs(null);

        // prepare args
        if (isset($args) && !is_null($args))
        {
            $args = json_decode(json_encode($args), true);

            $idn = new \idna_convert();

            // idn
            if (isset($args['domain']['name']) && isset($args['domain']['extension']))
            {
                // UTF-8 encoding
                if (!preg_match('//u', $args['domain']['name']))
                {
                    $args['domain']['name'] = utf8_encode($args['domain']['name']);
                }

                $args['domain']['name'] = $idn->encode($args['domain']['name']);
            }
            elseif (isset ($args['namePattern']))
            {
                $namePatternArr = explode('.', $args['namePattern'], 2);
                $tmpDomainName = $namePatternArr[0];

                // UTF-8 encoding
                if (!preg_match('//u', $tmpDomainName))
                {
                    $tmpDomainName = utf8_encode($tmpDomainName);
                }

                $tmpDomainName = $idn->encode($tmpDomainName);
                $args['namePattern'] = $tmpDomainName . '.' . $namePatternArr[1];
            }
            elseif (isset ($args['name']) && !is_array($args['name']))
            {
                // UTF-8 encoding
                if (!preg_match('//u', $args['name']))
                {
                    $args['name'] = utf8_encode($args['name']);
                }

                $args['name'] = $idn->encode($args['name']);
            }

            $this->request->setArgs($args);
        }

        // send request
        $result = $this->process($this->request);

        $resultValue = $result->getValue();

        $faultCode = $result->getFaultCode();

        if ($faultCode != 0)
        {
            $msg = $result->getFaultString();
            if ($value = $result->getValue())
            {
                if(is_array($value))
                {
                    if(isset($value['description']))
                        $msg .= ':<br> ' . $value['description'] . ' '.(isset($value['options']) ? '('.implode(',', $value['options']).')' : '' );
                    else
                        $msg .= implode(', ', $value);
                }
                else
                {
                    $msg .= ':<br> '.$value;
                }
            }

            throw new \Exception($msg, $faultCode);
        }

        return $resultValue;
    }

    protected function process(Request $r)
    {
        if ($this->debug)
        {
            echo $r->getRaw() . "\n";
        }

        $postValues = $r->getRaw();
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postValues);
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

        $ret = curl_exec($ch);



        $errno = curl_errno($ch);
        $this->error = curl_error($ch);

        // log message
        logModuleCall(
            'OpenProvider NL',
            $r->getCommand(),
            array(
                'postValues' => $postValues,
            ),
            array(
                'curlResponse' => $ret,
                'curlErrNo'    => $errno,
                'errorMessage' => $this->error,
            ),
            null,
            array(
                $this->password,
                htmlentities($this->password)
            )
        );

        if (!$ret)
        {
            throw new \Exception('Bad reply');
        }

        curl_close($ch);

        if ($errno)
        {
            return false;
        }

        if ($this->debug)
        {
            echo $ret . "\n";
        }

        return new Reply($ret);
    }

    /**
     *
     * @param array $createHandleArray
     * @return string Openprovider Customer Handle
     * @throws \Exception
     */
    public function createCustomerRequest($createHandleArray)
    {
        $result = $this->sendRequest('createCustomerRequest', $createHandleArray);
        return $result['handle'];
    }

    /**
     * Return reseller balance
     * @return array
     * @throws \Exception
     */
    public function getResellerBalance()
    {
        return $this->sendRequest('retrieveResellerRequest');
    }

    public function getUpdateMessage()
    {
        return $this->sendRequest('retrieveUpdateMessageRequest');
    }

    public function getResellerStatistics($task = '')
    {
        if(isset($task))
        {
            $args = [
                'task' => $task
            ];
        }
        else
            $args = [];

        return $this->sendRequest('retrieveStatisticsResellerRequest', $args);
    }

    /**
     * Returns all TLDs and the price
     * @return array
     * @throws \Exception
     */
    public function getTldsAndPricing()
    {
        $args = array(
            'withPrice'        => true,
        );
        return $this->sendRequest('searchExtensionRequest', $args);
    }

    /**
     * @param Domain $domain
     * @return array
     * @throws \Exception
     */
    protected function searchZoneDnsRequest(Domain $domain)
    {
        $args = array(
            'namePattern' => $domain->getFullName(),
            'type'        => 'master',
        );

        return $this->sendRequest('searchZoneDnsRequest', $args);
    }

    /**
     * @param DomainRegistration $domainRegistration
     * @return array
     * @throws \Exception
     */
    public function registerDomain(DomainRegistration $domainRegistration)
    {
        if($domainRegistration->dnsmanagement ==  1) {
            // check if zone exists
            $zoneResult = $this->searchZoneDnsRequest($domainRegistration->domain);

            if (0 == $zoneResult['total']) {
                // create a new DNS zone object
                $zoneArgs = array
                (
                    'domain' => $domainRegistration->domain,
                    'type' => 'master',
                    'templateName' => $domainRegistration->nsTemplateName
                );
                $this->sendRequest('createZoneDnsRequest', $zoneArgs);
            }
        }

        // register
        return $this->sendRequest('createDomainRequest', $domainRegistration);
    }

    /**
     * Get domain name servers
     * @param Domain $domain
     * @param bool $cache
     * @return array
     * @throws \Exception
     */
    public function getNameservers(Domain $domain, $cache = false)
    {
        $result = $this->retrieveDomainRequest($domain, $cache);

        $nameservers    =   array();
        foreach($result['nameServers'] as $ns)
        {
            $nameservers[] = (empty($ns['name']) ? $ns['ip'] : $ns['name']);
        }

        return $nameservers;
    }

    /**
     * Get registrar lock status
     * @param Domain $domain
     * @return bool
     * @throws \Exception
     */
    public function getRegistrarLock(Domain $domain)
    {
        $result         =   $this->retrieveDomainRequest($domain);
        $lockedStatus   =   $result['isLocked'] ? true : false;

        return $lockedStatus;
    }

    /**
     * Get the soft renewal date
     * @param Domain $domain
     * @return bool|string False if not date is provided.
     * @throws \Exception
     */
    public function getSoftRenewalExpiryDate(Domain $domain)
    {
        $result         =   $this->retrieveDomainRequest($domain);

        if(!isset($result['softQuarantineExpiryDate']))
            return false;

        return $result['softQuarantineExpiryDate'];
    }

    /**
     *
     * @param Domain $domain
     * @param $nameServers
     * @return array
     * @throws \Exception
     */
    public function saveNameservers(Domain $domain, $nameServers)
    {
        $args = array
        (
            'domain'        =>  $domain,
            'nameServers'   =>  $nameServers,
        );

        return $this->sendRequest('modifyDomainRequest', $args);
    }

    /**
     * Save registrar lock
     * @param Domain $domain
     * @param $lockStatus
     * @return array
     * @throws \Exception
     */
    public function saveRegistrarLock(Domain $domain, $lockStatus)
    {
        $args = array
        (
            'domain'    =>  $domain,
            'isLocked'  =>  $lockStatus,
        );

        return $this->sendRequest('modifyDomainRequest', $args);
    }

    /**
     * Save zone records
     * @param Domain $domain
     * @param $dnsRecordsArr
     * @throws \Exception
     */
    public function saveDNS(Domain $domain, $dnsRecordsArr)
    {
        $searchArgs = array
        (
            'namePattern' => $domain->getFullName(),
        );
        $result = $this->sendRequest('searchZoneDnsRequest', $searchArgs);

//        if($result['results'] == NULL)
//        {
//            // create a new DNS zone object
//            $zoneArgs = array
//            (
//                'domain' => $domainRegistration->domain,
//                'type' => 'master',
//                'templateName' => $domainRegistration->nsTemplateName
//            );
//            $this->sendRequest('createZoneDnsRequest', $zoneArgs);
//        }

        $args = array
        (
            'domain' => $domain,
            'type' => 'master',
            'records' => $dnsRecordsArr,
        );
        if ($result['total'] > 0)
        {
            $this->sendRequest('modifyZoneDnsRequest', $args);
        }
        else
        {
            $this->sendRequest('createZoneDnsRequest', $args);
        }
    }

    /**
     * Delete zone records
     * @param Domain $domain
     * @return bool
     */
    public function deleteDNS(Domain $domain)
    {
        try
        {
            $args = array(
                'domain' => $domain,
            );
            $this->sendRequest('deleteZoneDnsRequest', $args);
        } catch ( \Exception $e)
        {
            if($e->getMessage() == 'Zone specified is not found.')
                return true;
            else
                throw \Exception($e->getMessage());
        }
    }

    /**
     * Get zone records
     * @param Domain $domain
     * @return array
     * @throws \Exception
     */
    public function getDNS(Domain $domain)
    {
        $searchArgs = array
        (
            'namePattern' => $domain->getFullName(),
        );
        $result = $this->sendRequest('searchZoneDnsRequest', $searchArgs);

        if ($result['total'] > 0)
        {
            $retrieveArgs = array(
                'name' => $domain->getFullName(),
                'withHistory' => 0
            );
            return $this->sendRequest('retrieveZoneDnsRequest', $retrieveArgs);
        }
        else
        {
            return null;
        }
    }

    /**
     * Delete domain
     * @param Domain $domain
     * @throws \Exception
     */
    public function requestDelete(Domain $domain)
    {
        $args = array
        (
            'domain' => $domain,
        );

        // domain
        $this->sendRequest('deleteDomainRequest', $args);
    }

    /**
     * Get domain contact details
     * @param Domain $domain
     * @return array
     * @throws \Exception
     */
    public function getContactDetails(Domain $domain)
    {
        $domainInfo = $this->retrieveDomainRequest($domain);

        $contacts   =   array();
        foreach(APIConfig::$handlesNames as $key => $name)
        {
            if(empty($domainInfo[$key]))
            {
                continue;
            }

            $contacts[$name]    =   $this->retrieveCustomerRequest($domainInfo[$key]);
        }

        unset($contacts['Reseller']);
        unset($contacts['reseller']);

        return $contacts;
    }

    /**
     * Return array with contact information for given handle
     *
     * @param string $handle Customer handle
     * @param boolean $raw *optional* false If set to true, returns the raw output.
     * @return array
     * @throws \Exception
     */
    public function retrieveCustomerRequest($handle, $raw = false)
    {
        $args = array(
            'handle' => $handle,
        );
        $contact = $this->sendRequest('retrieveCustomerRequest', $args);

        if($raw == true)
            return $contact;

        $customerInfo = array();

        $customerInfo['First Name'] = $contact['name']['firstName'];
        $customerInfo['Last Name'] = $contact['name']['lastName'];
        $customerInfo['Company Name'] = $contact['companyName'];
        $customerInfo['Email Address'] = $contact['email'];
        $customerInfo['Address'] = $contact['address']['street'] . ' ' .
                $contact['address']['number'] . ' ' .
                $contact['address']['suffix'];
        $customerInfo['City'] = $contact['address']['city'];
        $customerInfo['State'] = $contact['address']['state'];
        $customerInfo['Zip Code'] = $contact['address']['zipcode'];
        $customerInfo['Country'] = $contact['address']['country'];
        $customerInfo['Phone Number'] = $contact['phone']['countryCode'] . '.' .
                $contact['phone']['areaCode'] .
                $contact['phone']['subscriberNumber'];

        return $customerInfo;
    }

    /**
     * Update the handle with the domain.
     *
     * @param Domain $domain
     * @param array $handles
     * @return void
     * @throws \Exception
     */
    public function modifyDomainCustomers(Domain $domain, array $handles)
    {
        $args = $handles;
        $args['domain'] = $domain;

        $this->sendRequest('modifyDomainRequest', $args);
    }

    /**
     * Search a domain in Openprovider.
     * @param array $filters
     * @return array
     * @throws \Exception
     */
    public function searchDomain($filters = [])
    {
        $args = [
            'offset' => 0, //Will only return results if more than 50 domains found
            'limit'  => 50
        ] + $filters;

        return $this->sendRequest('searchDomainRequest', $args);
    }

    /**
     * Transfer domain
     * @param DomainTransfer $domainTransfer
     * @throws \Exception
     */
    public function transferDomain(DomainTransfer $domainTransfer)
    {
        if($domainTransfer->dnsmanagement ==  1) {
            // check if zone exists

            $zoneResult = $this->searchZoneDnsRequest($domainTransfer->domain);

            if (0 == $zoneResult['total']) {
                // create a new DNS zone object
                $zoneArgs = array
                (
                    'domain' => $domainTransfer->domain,
                    'type' => 'master',
                    'templateName' => $domainTransfer->nsTemplateName
                );
                $this->sendRequest('createZoneDnsRequest', $zoneArgs);
            }
        }

        $this->sendRequest('transferDomainRequest', $domainTransfer);
    }

    /**
     *
     * @param Domain $domain
     * @param \DateTime $scheduled_date
     * @return array
     * @throws \Exception
     */
    public function modifyScheduledTransferDate(Domain $domain, $scheduled_date)
    {
        $args = array
        (
            'domain'        =>  $domain,
            'scheduledAt'   =>  $scheduled_date,
        );

        return $this->sendRequest('modifyDomainRequest', $args);
    }


    /**
     * Renew domain
     * @param Domain $domain
     * @param $period
     * @throws \Exception
     */
    public function renewDomain(Domain $domain, $period)
    {
        $args = array
        (
            'domain' => $domain,
            'period' => $period,
        );

        $this->sendRequest('renewDomainRequest', $args);
    }

    /**
     * Restore domain
     * @param Domain $domain
     * @throws \Exception
     */
    public function restoreDomain(Domain $domain)
    {
        $args = array
        (
            'domain' => $domain
        );

        $this->sendRequest('restoreDomainRequest', $args);
    }

    /**
     * Get domain epp code
     * @param Domain $domain
     * @return string
     * @throws \Exception
     */
    public function getEPPCode(Domain $domain)
    {
        $domainInfo = $this->retrieveDomainRequest($domain);
        return $domainInfo['authCode'];
    }

    /**
     * Creaat domain name server
     * @param DomainNameServer $nameServer
     * @throws \Exception
     */
    public function registerNameserver(DomainNameServer $nameServer)
    {
        $this->sendRequest('createNsRequest', $nameServer);
    }

    /**
     * Delete domain name server
     * @param DomainNameServer $nameServer
     * @throws \Exception
     */
    public function deleteNameserver(DomainNameServer $nameServer)
    {
        $this->sendRequest('deleteNsRequest', $nameServer);
    }


    /**
     *
     * @param string $request
     * @param DomainNameServer $nameServer
     * @param string $currentIp
     * @throws \Exception
     */
    public function nameserverRequest($request, DomainNameServer $nameServer, $currentIp = null)
    {
        if ('modify' == $request)
        {
            // check current IP address
            $retrieveResult = $this->sendRequest('retrieveNsRequest', $nameServer);
            if ($retrieveResult['ip'] != $currentIp)
            {
                throw new \Exception('Current IP Address is incorrect');
            }
        }

        $this->sendRequest($request . 'NsRequest', $nameServer);
    }

    /**
     * Get information about domain
     * @param Domain $domain
     * @param bool $cache
     * @return array
     * @throws \Exception
     */
    public function retrieveDomainRequest(Domain $domain, $cache = false)
    {
        $domain_name = $domain->getFullName();

        if(!isset($this->cache[$domain_name]) || $cache == false)
        {
            $args = array
            (
                'domain' => $domain,
            );

            $this->cache[$domain_name] =  $this->sendRequest('retrieveDomainRequest', $args);
        }

        return $this->cache[$domain_name];
    }

    /**
     * Enable/Disable domain auto renew
     * @param Domain $domain
     * @param $autoRenew
     * @return array
     * @throws \Exception
     */
    public function setAutoRenew(Domain $domain, $autoRenew)
    {
        $args = array
        (
            'domain'     =>  $domain,
            'autorenew' =>  $autoRenew
        );

        return $this->sendRequest('modifyDomainRequest', $args);
    }

    /**
     * Enable/Disable domain identity protection
     * @param Domain $domain
     * @param $identityProtection
     * @return array
     * @throws \Exception
     */
    public function setPrivateWhoisEnabled (Domain $domain, $identityProtection)
    {
        $args = array
        (
            'domain'                =>  $domain,
            'isPrivateWhoisEnabled' =>  $identityProtection
        );


        return $this->sendRequest('modifyDomainRequest', $args);
    }

    /**
     * Check domain availability
     * @param Domain $domain
     * @return array
     * @throws \Exception
     */
    public function checkDomain(Domain $domain)
    {
        $args = array
        (
            'domains'               =>  array
            (
                'item'              =>  array
                (
                    'name'          =>  $domain->name,
                    'extension'     =>  $domain->extension
                )
            )
        );

        return $this->sendRequest('checkDomainRequest', $args);
    }

    /**
     * Check domain availability.
     * Limit on the number of domains is 15 per time.
     * If number of domains is over than 15 it will check only 15 first domains.
     *
     * @param array of \OpenProvider\API\Domain $domains
     * @return array
     * @throws \Exception
     * @see https://doc.openprovider.eu/API_Module_Domain_checkDomainRequest
     */
    public function checkDomainArray($domains)
    {
        $domainsLimitPerRequest = 15;
        $domainArgs = [];
        foreach($domains as $domain)
        {
            $tmpArg['name']         = $domain->name;
            $tmpArg['extension']    = $domain->extension;
            $domainArgs[]           = $tmpArg;
            $domainsLimitPerRequest   -= 1;
            if ($domainsLimitPerRequest == 0)
                break;
        }

        $args = array
        (
            'domains'               =>  $domainArgs
        );

        return $this->sendRequest('checkDomainRequest', $args);
    }

    /**
     * Search for DNS template names
     * @return array
     * @throws \Exception
     */
    public function searchTemplateDnsRequest()
    {
        return $this->sendRequest('searchTemplateDnsRequest');
    }

    /**
     * @param Domain $domain
     * @return array
     * @throws \Exception
     */
    public function tryAgain(Domain $domain)
    {
        $args = array
        (
            'domain'    =>  $domain
        );

        return $this->sendRequest('tryAgainDomainRequest', $args);
    }

    /**
     * Get the DNS Single Domain Token
     * @param Domain $domain
     * @return array
     * @throws \Exception
     */
    public function getDnsSingleDomainTokenUrl($domain)
    {
        $args = array
        (
            'domain'    =>  $domain
        );

        return $this->sendRequest('generateSingleDomainTokenRequest', $args);
    }

    /**
     * Get All SSL Orders
     * @return array
     * @throws \Exception
     */
    public function searchSSL()
    {
        return $this->sendRequest('searchOrderSslCertRequest');
    }

    /**
     * Get tags list
     *
     * @return array
     * @throws \Openprovider\Api\Rest\Client\Base\ApiException
     */
    public function listTagsRequest()
    {
        return $this->tagService->listTags()->getData()->getResults();
    }
}
