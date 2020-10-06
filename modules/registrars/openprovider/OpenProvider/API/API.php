<?php

namespace OpenProvider\API;

use OpenProvider\WhmcsRegistrar\src\Configuration;

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

    /**
     * API constructor.
     */
    public function __construct()
    {
        $this->timeout = \OpenProvider\API\APIConfig::$curlTimeout;
        $this->request = new \OpenProvider\API\Request();
    }

    /**
     * @param $params
     * @param int $debug
     */
    public function setParams($params, $debug = 0)
    {
        if(isset($params['test_mode']) && $params['test_mode'] == 'on')
            $this->url = Configuration::get('api_url_cte');
        else
            $this->url = Configuration::get('api_url');

        $this->request->setAuth(array(
            'username' => $params["Username"],
            'password' => $params["Password"],
        ));

        $this->username     =   $params['Username'];
        $this->password     =   $params['Password'];

        $this->debug        =   $debug;
    }

    public function modifyCustomer(\OpenProvider\API\Customer $customer)
    {
        $args = $customer;
        $this->sendRequest('modifyCustomerRequest', $args);
    }

    public function createCustomerInOPdatabase(\OpenProvider\API\Customer $customer)
    {
        $args = $customer;
        $result = $this->sendRequest('createCustomerRequest', $args);

        return $result;
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

    protected function process(\OpenProvider\API\Request $r)
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
                ));

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

        return new \OpenProvider\API\Reply($ret);
    }

    /**
     *
     * @param array $createHandleArray
     * @return string Openprovider Customer Handle
     */
    public function createCustomerRequest($createHandleArray)
    {
        $result = $this->sendRequest('createCustomerRequest', $createHandleArray);
        return $result['handle'];
    }

    public function searchCustomerRequest($searchCustomerArray)
    {
        return $this->sendRequest('searchCustomerRequest', $searchCustomerArray);
    }

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

    protected function searchZoneDnsRequest(\OpenProvider\API\Domain $domain)
    {
        $args = array(
            'namePattern' => $domain->getFullName(),
            'type'        => 'master',
        );

        return $this->sendRequest('searchZoneDnsRequest', $args);
    }

    public function registerDomain(\OpenProvider\API\DomainRegistration $domainRegistration)
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
     * @param \OpenProvider\API\Domain $domain
     * @param bool $cache
     * @return array
     */
    public function getNameservers(\OpenProvider\API\Domain $domain, $cache = false)
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
     * @param \OpenProvider\API\Domain $domain
     * @return bool
     */
    public function getRegistrarLock(\OpenProvider\API\Domain $domain)
    {
        $result         =   $this->retrieveDomainRequest($domain);
        $lockedStatus   =   $result['isLocked'] ? true : false;

        return $lockedStatus;
    }

    /**
     * Get the soft renewal date
     * @param \OpenProvider\API\Domain $domain
     * @return bool|string False if not date is provided.
     */
    public function getSoftRenewalExpiryDate(\OpenProvider\API\Domain $domain)
    {
        $result         =   $this->retrieveDomainRequest($domain);

        if(!isset($result['softQuarantineExpiryDate']))
            return false;

        return $result['softQuarantineExpiryDate'];
    }

    /**
     *
     * @param \OpenProvider\API\Domain $domain
     * @param type $nameServers
     * @return type
     */
    public function saveNameservers(\OpenProvider\API\Domain $domain, $nameServers)
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
     * @param \OpenProvider\API\Domain $domain
     * @param type $lockStatus
     * @return type
     */
    public function saveRegistrarLock(\OpenProvider\API\Domain $domain, $lockStatus)
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
     * @param \OpenProvider\API\Domain $domain
     * @param type $dnsRecordsArr
     */
    public function saveDNS(\OpenProvider\API\Domain $domain, $dnsRecordsArr)
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
     * @param \OpenProvider\API\Domain $domain
     */
    public function deleteDNS(\OpenProvider\API\Domain $domain)
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
     * @param \OpenProvider\API\Domain $domain
     * @return type
     */
    public function getDNS(\OpenProvider\API\Domain $domain)
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
     * @param \OpenProvider\API\Domain $domain
     */
    public function requestDelete(\OpenProvider\API\Domain $domain)
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
     * @param \OpenProvider\API\Domain $domain
     * @return type
     */
    public function getContactDetails(\OpenProvider\API\Domain $domain)
    {
        $domainInfo = $this->retrieveDomainRequest($domain);

        $contacts   =   array();
        foreach(\OpenProvider\API\APIConfig::$handlesNames as $key => $name)
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
     * @param \OpenProvider\API\Domain $domain
     * @param array $handles
     * @return void
     */
    public function modifyDomainCustomers(\OpenProvider\API\Domain $domain, $handles)
    {
        $args = $handles;
        $args['domain'] = $domain;

        $this->sendRequest('modifyDomainRequest', $args);
    }

    /**
     * Search a domain in Openprovider.
     * @param array $filters
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
     * @param \OpenProvider\API\DomainTransfer $domainTransfer
     */
    public function transferDomain(\OpenProvider\API\DomainTransfer $domainTransfer)
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
     * @param \OpenProvider\API\Domain $domain
     * @param \DateTime $scheduled_date
     * @return array
     */
    public function modifyScheduledTransferDate(\OpenProvider\API\Domain $domain, $scheduled_date)
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
     * @param \OpenProvider\API\Domain $domain
     * @param type $period
     */
    public function renewDomain(\OpenProvider\API\Domain $domain, $period)
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
     * @param \OpenProvider\API\Domain $domain
     * @param type $period
     */
    public function restoreDomain(\OpenProvider\API\Domain $domain)
    {
        $args = array
        (
            'domain' => $domain
        );

        $this->sendRequest('restoreDomainRequest', $args);
    }

    /**
     * Get domain epp code
     * @param \OpenProvider\API\Domain $domain
     * @return type
     */
    public function getEPPCode(\OpenProvider\API\Domain $domain)
    {
        $domainInfo = $this->retrieveDomainRequest($domain);
        return $domainInfo['authCode'];
    }

    /**
     * Creaat domain name server
     * @param \OpenProvider\API\DomainNameServer $nameServer
     */
    public function registerNameserver(\OpenProvider\API\DomainNameServer $nameServer)
    {
        $this->sendRequest('createNsRequest', $nameServer);
    }

    /**
     * Delete domain name server
     * @param \OpenProvider\API\DomainNameServer $nameServer
     */
    public function deleteNameserver(\OpenProvider\API\DomainNameServer $nameServer)
    {
        $this->sendRequest('deleteNsRequest', $nameServer);
    }


    /**
     *
     * @param string $request
     * @param \OpenProvider\API\DomainNameServer $nameServer
     * @param string $currentIp
     * @throws \Exception
     */
    public function nameserverRequest($request, \OpenProvider\API\DomainNameServer $nameServer, $currentIp = null)
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
     * @param \OpenProvider\API\Domain $domain
     * @param bool $cache
     * @return array
     * @throws \Exception
     */
    public function retrieveDomainRequest(\OpenProvider\API\Domain $domain, $cache = false)
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
     * @param \OpenProvider\API\Domain $domain
     * @param type $autoRenew
     * @return type
     */
    public function setAutoRenew(\OpenProvider\API\Domain $domain, $autoRenew)
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
     * @param \OpenProvider\API\Domain $domain
     * @param type $identityProtection
     * @return type
     */
    public function setPrivateWhoisEnabled (\OpenProvider\API\Domain $domain, $identityProtection)
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
     * @param \OpenProvider\API\Domain $domain
     * @return type
     */
    public function checkDomain(\OpenProvider\API\Domain $domain)
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
     * Check domain availability
     * @param array of \OpenProvider\API\Domain $domainss
     * @return type
     */
    public function checkDomainArray($domains)
    {
        $domainArgs = [];
        foreach($domains as $domain)
        {
            $tmpArg['name']         = $domain->name;
            $tmpArg['extension']    = $domain->extension;
            $domainArgs[]           = $tmpArg;
        }

        $args = array
        (
            'domains'               =>  $domainArgs
        );

        return $this->sendRequest('checkDomainRequest', $args);
    }

    /**
     * Search for DNS template names
     * @return type
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
    public function tryAgain(\OpenProvider\API\Domain $domain)
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

}
