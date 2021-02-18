<?php

namespace OpenProvider\API;

use Openprovider\Api\Rest\Client\Helpers\Api\TagServiceApi;
use OpenProvider\WhmcsRegistrar\src\Configuration;
use WeDevelopCoffee\wPower\Models\Registrar;
use GuzzleHttp6\Client as HttpClient;

require_once (realpath(__DIR__.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'classes'.DIRECTORY_SEPARATOR.'idna_convert.class.php'));

class JsonAPI
{
    // Debug statuses
    const DEBUG_ENABLED = 1;
    const DEBUG_DISABLED = 0;

    // Test mode
    const TEST_MODE_ON = 1;
    const TEST_MODE_OFF = 0;

    private $request;

    private $timeout;

    private $url = null;

    private $token = null;

    private $error = null;

    private $debug = null;

    private $tokenSessionKey = null;

    /* ==================== MAIN LOGIC ==================== */

    /**
     * JsonAPI constructor.
     *
     * @param null $params
     * @throws \Exception
     */
    public function __construct($params = null)
    {
        session_start();

        $this->timeout = APIConfig::$curlTimeout;
        $this->request = new RequestJSON();

        if (is_null($params))
            $params = (new Registrar())->getRegistrarData()['openprovider'];

        if (isset($params['test_mode']) && $params['test_mode'] == 'on')
            $this->setTestMode(self::TEST_MODE_ON);
        else
            $this->setTestMode(self::TEST_MODE_OFF);

        $isNotExistUsernameAndPassword = !isset($params['Username']) || empty($params['Username'])
            || !isset($params['Password']) || empty($params['Password']);

        if ($isNotExistUsernameAndPassword)
            return $this;

        // if api object haven't token,
        // we get it from openprovider by authLoginRequest method
        // And store token in php session
        $tokenNameHash = md5("{$this->url}-{$params['Username']}-{$params['Password']}");
        $sessionTokenVariable = "token-{$tokenNameHash}";

        $this->tokenSessionKey = $sessionTokenVariable;

        if (isset($_SESSION[$sessionTokenVariable]) && !empty($_SESSION[$sessionTokenVariable]))
            $this->token = $_SESSION[$sessionTokenVariable];
        else {
            try {
                $reply                           = $this->authLoginRequest($params['Username'], $params['Password']);
                $this->token                     = $reply['token'];
                $_SESSION[$sessionTokenVariable] = $reply['token'];
            } catch (\Exception $ex) {}
        }

        return $this;
    }

    /**
     * Method return token from this class
     * or send request to openprovider
     *
     * @param string $username
     * @param string $password
     * @return ReplyJSON
     */
    public function authLoginRequest(
        string $username, string $password
    ): array
    {
        $args     = [
            'username' => $username,
            'password' => $password,
            'ip'       => '0.0.0.0'
        ];

        return $this->_sendRequest(
            APIEndpoints::AUTH_LOGIN,
            $args,
            [],
            APIMethods::POST
        );
    }

    /**
     * @param string $endpoint
     * @param array $args
     * @param array $substitutionArgs
     * @param string $method
     * @return array
     * @throws \Exception
     */
    private function _sendRequest(
        string $endpoint,
        array $args = [],
        array $substitutionArgs = [],
        string $method = APIMethods::GET
    ): array
    {
        $url = $this->url.'/v1beta';

        // prepare request
        $this->request->setEndpoint($endpoint)
            ->setMethod($method)
            ->setArgs([])
            ->processUrl($url, $substitutionArgs);

        $args = json_decode(json_encode($args), true);

        if (!empty($args)) {
            $idn = new \idna_convert();

            // idn
            if (isset($args['domain']['name']) && isset($args['domain']['extension'])) {
                // UTF-8 encoding
                if (!preg_match('//u', $args['domain']['name'])) {
                    $args['domain']['name'] = utf8_encode($args['domain']['name']);
                }

                $args['domain']['name'] = $idn->encode($args['domain']['name']);
            } elseif (isset ($args['namePattern'])) {
                $namePatternArr = explode('.', $args['namePattern'], 2);
                $tmpDomainName  = $namePatternArr[0];

                // UTF-8 encoding
                if (!preg_match('//u', $tmpDomainName)) {
                    $tmpDomainName = utf8_encode($tmpDomainName);
                }

                $tmpDomainName       = $idn->encode($tmpDomainName);
                $args['namePattern'] = $tmpDomainName . '.' . $namePatternArr[1];
            } elseif (isset ($args['name']) && !is_array($args['name'])) {
                // UTF-8 encoding
                if (!preg_match('//u', $args['name'])) {
                    $args['name'] = utf8_encode($args['name']);
                }

                $args['name'] = $idn->encode($args['name']);
            }

            $this->request->setArgs($args);
        }

        // send request
        $result = $this->_process($this->request);

        $resultValue = $result->getValue();

        $faultCode = $result->getFaultCode();

        if ($faultCode != 0) {
            $msg = $result->getFaultString();
            if ($value = $result->getValue()) {
                if (is_object($value)) {
                    if (isset($value->description))
                        $msg .= ':<br> '
                            . json_encode($value->description) . ' '
                            . (isset($value->options)
                                ? '(' . implode(',', $value->options) . ')'
                                : '');
                    else
                        $msg .= ':<br>' . json_encode($value);
                } else {
                    $msg .= ':<br> ' . json_encode($value);
                }
            }

            throw new \Exception($msg, $faultCode);
        }

        return json_decode(json_encode($resultValue), true);
    }

    /**
     * @param \OpenProvider\API\RequestJson $r
     * @return ReplyJSON|bool
     * @throws \Exception
     */
    private function _process(\OpenProvider\API\RequestJSON $r)
    {
        if ($this->debug) {
            echo $r->getArgsJson() . "\n";
        }

        $url = $r->getUrl();

        $requestHeader = [
            'Content-type: application/json',
        ];

        if ($this->token)
            $requestHeader[] = 'Authorization: Bearer ' . $this->token;


        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $r->getMethod());
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $requestHeader);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

        switch ($r->getMethod()) {
            case APIMethods::POST:
            case APIMethods::PUT:
            case APIMethods::DELETE:
                $requestBody = $r->getArgsJson();


                curl_setopt($ch, CURLOPT_POSTFIELDS, $requestBody);

                break;
            case APIMethods::GET:
                $url = $url . '?' . http_build_query($r->getArgs());

                curl_setopt($ch, CURLOPT_URL, $url);

                break;
        }

        $ret = curl_exec($ch);

        $errno       = curl_errno($ch);
        $this->error = curl_error($ch);

        curl_close($ch);


        $postValuesToLog = $r->getArgs();
        if ($postValuesToLog['password'])
            unset($postValuesToLog['password']);

        // log message
        logModuleCall(
            'OpenProvider NL',
            "{$r->getMethod()} {$r->getUrl()}",
            array(
                'params' => json_encode($postValuesToLog),
            ),
            array(
                'curlResponse' => $ret,
                'curlErrNo'    => $errno,
                'errorMessage' => $this->error,
            ),
            null);

        if (!$ret) {
            throw new \Exception('Bad reply');
        }

        if ($errno) {
            return false;
        }

        if ($this->debug) {
            echo $ret . "\n";
        }

        return new ReplyJSON($ret);
    }


    /* ==================== END MAIN LOGIC ==================== */


    /* ==================== REQUESTS ==================== */

    // Customers
    /**
     * @param array $customer
     * @return object
     * @throws \Exception
     */
    public function updateCustomerRequest(Customer $customer): array
    {
        $substitutionArgs = [
            'handle' => $customer->handle,
        ];

        $args = json_decode(json_encode($customer), true);

        $customerArgs = APITools::convertKeysFromCamelCaseToUnderscore($args);

        return $this->_sendRequest(
            APIEndpoints::CUSTOMERS_HANDLE,
            $customerArgs,
            $substitutionArgs,
            APIMethods::PUT
        );
    }

    /**
     * @param array $customer
     * @return object
     * @throws \Exception
     */
    public function createCustomerRequest(Customer $customer): array
    {
        $args = APITools::convertKeysFromCamelCaseToUnderscore(json_decode(json_encode($customer), true));

        return $this->_sendRequest(APIEndpoints::CUSTOMERS, $args, [], APIMethods::POST);
    }

    /**
     * @param array $args
     * @return array
     * @throws \Exception
     */
    public function listCustomersRequest(array $args): array
    {
        return $this->_sendRequest(APIEndpoints::CUSTOMERS, $args);
    }

    /**
     * @param string $handle
     * @return object
     * @throws \Exception
     */
    public function deleteCustomerRequest(string $handle)
    {
        $substitutionArgs = [
            'handle' => $handle,
        ];
        return $this->_sendRequest(
            APIEndpoints::CUSTOMERS_HANDLE,
            [],
            $substitutionArgs,
            APIMethods::DELETE
        );
    }

    /**
     * @param array $args
     * @return array
     * @throws \Exception
     */
    public function listCustomersVerificationsEmailsDomainsRequest(
        array $args
    ): array
    {
        return $this->_sendRequest(
            APIEndpoints::CUSTOMERS_VERIFICATIONS_EMAILS_DOMAINS,
            $args
        );
    }

    /**
     * @param string $email
     * @param string $language
     * @param string $tag
     * @return array
     * @throws \Exception
     */
    public function restartCustomersVerificationsEmailsRequest(
        string $email, string $language = '', string $tag = ''
    ): array
    {
        $args = [
            'email'    => $email,
            'language' => $language,
            'tag'      => $tag,
        ];

        return $this->_sendRequest(
            APIEndpoints::CUSTOMERS_VERIFICATIONS_EMAILS_RESTART,
            $args,
            [],
            APIMethods::POST
        );
    }

    /**
     * @param string $email
     * @param string $handle
     * @param string $language
     * @param string $tag
     * @return array
     * @throws \Exception
     */
    public function startCustomersVerificationsEmailsRequest(
        string $email,
        string $handle = '',
        string $language = '',
        string $tag = ''
    ): array
    {
        $args = [
            'email'    => $email,
            'handle'   => $handle,
            'language' => $language,
            'tag'      => $tag,
        ];

        return $this->_sendRequest(
            APIEndpoints::CUSTOMERS_VERIFICATIONS_EMAILS_START,
            $args,
            [],
            APIMethods::POST
        );
    }

    /**
     * @param string $handle
     * @return object
     * @throws \Exception
     */
    public function getCustomerRequest(string $handle, bool $row = false): array
    {
        $substitutionArgs = [
            'handle' => $handle,
        ];

        $contact = $this->_sendRequest(
            APIEndpoints::CUSTOMERS_HANDLE,
            [],
            $substitutionArgs
        );

        if ($row)
            return $contact;

        $customerInfo = array();

        $customerInfo['First Name']    = $contact['name']['first_name'];
        $customerInfo['Last Name']     = $contact['name']['last_name'];
        $customerInfo['Company Name']  = $contact['company_name'];
        $customerInfo['Email Address'] = $contact['email'];
        $customerInfo['Address']       = $contact['address']['street'] . ' ' .
            $contact['address']['number'] . ' ' .
            $contact['address']['suffix'];
        $customerInfo['City']          = $contact['address']['city'];
        $customerInfo['State']         = $contact['address']['state'];
        $customerInfo['Zip Code']      = $contact['address']['zipcode'];
        $customerInfo['Country']       = $contact['address']['country'];
        $customerInfo['Phone Number']  = $contact['phone']['country_code'] . '.' .
            $contact['phone']['area_code'] .
            $contact['phone']['subscriber_number'];

        return $customerInfo;
    }

    public function updateCustomerTagsRequest(string $handle, $tags)
    {
        $substitutionArgs = [
            'handle' => $handle,
        ];

        $args = [
            'handle' => $handle,
            'tags' => $tags,
        ];

        return $this->_sendRequest(
            APIEndpoints::CUSTOMERS_HANDLE,
            $args,
            $substitutionArgs,
            APIMethods::PUT
        );
    }


    // Domains

    /**
     * @param $args - array or Domain object
     * @return array
     * @throws \Exception
     */
    public function getDomainRequest($domain)
    {
        $params = [];

        if (is_array($domain)) {
            if (isset($domain['name']))
                $params['domain_name_pattern'] = $domain['name'];
            if (isset($domain['extension']))
                $params['extension'] = $domain['extension'];
            if (isset($domain['domain']))
                $params['full_name'] = $domain['domain'];
            if (isset($domain['with_verification_email']))
                $params['with_verification_email'] = $domain['with_verification_email'];
            if (is_array($domain['domain'])) {
                if (isset($domain['domain']['name']))
                    $params['domain_name_pattern'] = $domain['domain']['name'];
                if (isset($domain['domain']['extension']))
                    $params['extension'] = $domain['domain']['extension'];
            }

        } else if (is_object($domain)) {
            if (isset($domain->id)) {
                $params['id'] = $domain->id;
            }
            if (isset($domain->name)) {
                $params['domain_name_pattern'] = $domain->name;
            }
            if (isset($domain->extension)) {
                $params['extension'] = $domain->extension;
            }
            if (isset($params['domain_name_pattern']) && isset($params['extension']))
                $params['full_name'] = "{$params['domain_name_pattern']}.{$params['extension']}";
        }

        $result = $this->_sendRequest(APIEndpoints::DOMAINS, $params);
        return $result['results'][0];
    }

    /**
     * @param int $id
     * @param $args
     * @return array
     * @throws \Exception
     */
    public function updateDomainRequest(int $id, $args): array
    {
        $substitutionArgs = [
            'id' => $id,
        ];
        return $this->_sendRequest(
            APIEndpoints::DOMAINS_ID,
            $args,
            $substitutionArgs,
            APIMethods::PUT
        );
    }

    public function deleteDomainRequest(Domain $domain)
    {
        $domainInfo = $this->getDomainRequest($domain);

        $substitutionArgs = [
            'id' => $domainInfo['id'],
        ];

        return $this->_sendRequest(
            APIEndpoints::DOMAINS_ID,
            [],
            $substitutionArgs,
            APIMethods::DELETE
        );
    }

    public function registerDomainRequest(DomainRegistration $domainRegistration)
    {
        if ($domainRegistration->dnsmanagement == 1) {
            // check if zone exists
            $zoneArgs = [
                'name_pattern' => $domainRegistration->domain->getFullName(),
                'type' => 'master',
            ];
            $zoneResult = $this->listDNSZoneRequest($zoneArgs);

            if ($zoneResult['total'] == 0) {
                $newZoneArgs = [
                    'domain' => json_decode(json_encode($domainRegistration->domain), true),
                    'type' => 'master',
                    'template_name' => $domainRegistration->nsTemplateName,
                ];

                $this->createDNSZoneRequest($newZoneArgs);
            }
        }

        $domainArgs = json_decode(json_encode($domainRegistration), true);
        $domainArgs = APITools::convertKeysFromCamelCaseToUnderscore($domainArgs);

        // delete extra values
        if (count($domainArgs['additional_data']) == 0)
            unset($domainArgs['additional_data']);

        unset($domainArgs['dnsmanagement']);

        return $this->_sendRequest(
            APIEndpoints::DOMAINS,
            $domainArgs,
            [],
            APIMethods::POST
        );
    }

    public function transferDomainRequest(DomainTransfer $domainTransfer)
    {
        if ($domainTransfer->dnsmanagement == 1) {
            // check if zone exists
            $zoneArgs = [
                'name_pattern' => $domainTransfer->domain->getFullName(),
                'type' => 'master',
            ];
            $zoneResult = $this->listDNSZoneRequest($zoneArgs);

            if ($zoneResult['total'] == 0) {
                $newZoneArgs = [
                    'domain' => json_decode(json_encode($domainTransfer->domain), true),
                    'type' => 'master',
                    'template_name' => $domainTransfer->nsTemplateName,
                ];

                $this->createDNSZoneRequest($newZoneArgs);
            }
        }

        $domainArgs = json_decode(json_encode($domainTransfer), true);
        $domainArgs = APITools::convertKeysFromCamelCaseToUnderscore($domainArgs);

        // delete extra values
        if (count($domainArgs['additional_data']) == 0)
            unset($domainArgs['additional_data']);

        unset($domainArgs['dnsmanagement']);

        return $this->_sendRequest(
            APIEndpoints::DOMAINS_TRANSFER,
            $domainArgs,
            [],
            APIMethods::POST
        );
    }

    public function getDomainAuthCodeRequest(Domain $domain)
    {
        $reply = $this->getDomainRequest($domain);

        return $reply['auth_code'];
    }

    public function getDomainNameserversRequst(Domain $domain): array
    {
        $reply = $this->getDomainRequest($domain);

        $nameservers = [];

        foreach ($reply['name_servers'] as $ns) {
            $nameservers[] = (empty($ns['name']) ? $ns['ip'] : $ns['name']);
        }

        return $nameservers;
    }

    public function updateDomainNameserversRequest(Domain $domain, $nameservers)
    {
        $result = $this->getDomainRequest($domain);

        $args = [
            'name_servers' => $nameservers,
        ];
        return $this->updateDomainRequest($result['id'], $args);
    }

    /**
     * @param Domain $domain
     * @return array
     * @throws \Exception
     */
    public function getDomainContactsRequest(Domain $domain): array
    {
        $domainArgs = [
            'domain' => $domain->getFullName(),
        ];

        $domainInfo = $this->getDomainRequest($domainArgs);

        $contacts = array();
        foreach (\OpenProvider\API\APIConfig::$handlesNamesFromJson as $key => $name) {
            $isContinueContact = empty($domainInfo[$key])
                || $key == 'reseller_handle';

            if ($isContinueContact) continue;

            $contacts[$name] = $this->getCustomerRequest($domainInfo[$key]);
        }

        return $contacts;
    }

    public function getDomainRegistrarLockRequest(Domain $domain)
    {
        $result = $this->getDomainRequest($domain);

        return $result['is_locked'];
    }

    public function updateDomainRegistrarLockRequest(Domain $domain, bool $lockStatus)
    {
        $domainInfo = $this->getDomainRequest($domain);

        $args = [
            'is_locked' => $lockStatus,
        ];

        return $this->updateDomainRequest($domainInfo['id'], $args);
    }

    public function restoreDomainRequest(Domain $domain)
    {
        $domainInfo = $this->getDomainRequest($domain);

        $substitutionArgs = [
            'id' => $domainInfo['id'],
        ];

        $args = [
            'domain' => $domain,
            'id'     => $domainInfo['id'],
        ];

        return $this->_sendRequest(
            APIEndpoints::DOMAINS_ID_RESTORE,
            $args,
            $substitutionArgs,
            APIMethods::POST
        );
    }

    public function renewDomainRequest(Domain $domain, $period)
    {
        $domainInfo = $this->getDomainRequest($domain);

        $substitutionArgs = [
            'id' => $domainInfo['id'],
        ];

        $args = [
            'domain' => $domain,
            'id'     => $domainInfo['id'],
            'period' => $period,
        ];

        return $this->_sendRequest(
            APIEndpoints::DOMAINS_ID_RENEW,
            $args,
            $substitutionArgs,
            APIMethods::POST
        );
    }

    public function getDomainSoftQuarantineExpiryDate(Domain $domain)
    {
        $domainInfo = $this->getDomainRequest($domain);

        $isExistSoftQuarantineExpiryDate = !isset($domainInfo['soft_quarantine_expiry_date'])
            && !empty($domainInfo['soft_quarantine_expiry_date']);
        if ($isExistSoftQuarantineExpiryDate)
            return $domainInfo['soft_quarantine_expiry_date'];

        return false;
    }

    public function updateDomainAutorenewRequest(Domain $domain, $autorenew)
    {
        $domainInfo = $this->getDomainRequest($domain);

        $args = [
            'autorenew' => $autorenew,
        ];

        return $this->updateDomainRequest($domainInfo['id'], $args);
    }

    // DNS

    public function listDNSZoneRequest($args)
    {
        return $this->_sendRequest(
            APIEndpoints::DNS_ZONES,
            $args
        );
    }

    public function getDNSZoneRecordsRequest(Domain $domain): array
    {
        $substitutionArgs = [
            'name' => $domain->getFullName(),
        ];

        return $this->_sendRequest(
            APIEndpoints::DNS_ZONES_NAME_RECORDS,
            [],
            $substitutionArgs
        );
    }

    public function createDNSZoneRequest($args)
    {
        return $this->_sendRequest(
            APIEndpoints::DNS_ZONES,
            $args,
            [],
            APIMethods::POST
        );
    }

    public function updateDNSZoneRequest(Domain $domain, $args)
    {
        $substitutionArgs = [
            'name' => $domain->getFullName(),
        ];

        $args['records']['replace'] = $args['records'];

        return $this->_sendRequest(
            APIEndpoints::DNS_ZONES_NAME,
            $args,
            $substitutionArgs,
            APIMethods::PUT
        );
    }

    public function createOrUpdateDNSZoneRequest(Domain $domain, $dnsRecords)
    {
        $args = [
            'domain' => json_decode(json_encode($domain), true),
            'type' => 'master',
            'records' => $dnsRecords,
        ];

        $DNSZone = $this->getDNSZoneRecordsRequest($domain);

        if ($DNSZone['total'] > 0) {
            return $this->updateDNSZoneRequest($domain, $args);
        }

        return $this->createDNSZoneRequest($args);
    }

    public function deleteDNSZoneRequest(Domain $domain)
    {
        $substitutionArgs = [
            'name' => $domain->getFullName(),
        ];

        return $this->_sendRequest(
            APIEndpoints::DNS_ZONES_NAME,
            [],
            $substitutionArgs,
            APIMethods::DELETE
        );
    }

    public function listDnsNameserversRequest($args): array
    {
        return $this->_sendRequest(APIEndpoints::DNS_NAMESERVERS, $args);
    }

    public function createDnsNameserversRequest(DomainNameServer $nameserver)
    {
        $args = json_decode(json_encode($nameserver), true);
        return $this->_sendRequest(APIEndpoints::DNS_NAMESERVERS, $args, [], APIMethods::POST);
    }

    public function updateDnsNameserversRequest(DomainNameServer $nameserver, $currentIp = null)
    {
        $retrieveResult = $this->getDnsNameserversRequest($nameserver);
        if ($retrieveResult['ip'] != $currentIp)
        {
            throw new \Exception('Current IP Address is incorrect');
        }

        $substitutionArgs = [
            'name' => $nameserver->name,
        ];

        $args = json_decode(json_encode($nameserver), true);

        return $this->_sendRequest(
            APIEndpoints::DNS_NAMESERVERS_NAME,
            $args,
            $substitutionArgs,
            APIMethods::PUT
        );
    }

    public function getDnsNameserversRequest(DomainNameServer $nameserver)
    {
        $substitutionArgs = [
            'name' => $nameserver->name,
        ];

        return $this->_sendRequest(
            APIEndpoints::DNS_NAMESERVERS_NAME,
            [],
            $substitutionArgs
        );
    }

    public function deleteDnsNameserversRequest(DomainNameServer $nameserver)
    {
        $substitutionArgs = [
            'name' => $nameserver,
        ];

        return $this->_sendRequest(
            APIEndpoints::DNS_NAMESERVERS_NAME,
            [],
            $substitutionArgs,
            APIMethods::DELETE
        );
    }


    // Tags

    /**
     * @throws \Openprovider\Api\Rest\Client\Base\ApiException
     */
    public function listTagsRequest()
    {
        $httpClient = new HttpClient();
        $configuration = new \Openprovider\Api\Rest\Client\Base\Configuration();
        $configuration->setAccessToken($this->token);
        $configuration->setHost($this->url);

        $tagClient = new TagServiceApi($httpClient, $configuration);
        $tags = $tagClient->listTags();

        return $tags->getData()->getResults();
    }


    // Tlds

    public function listTldsRequest($args = []) {
        $args = array_merge($args, [
            'with_price' => true,
        ]);

        return $this->_sendRequest(
            APIEndpoints::TLDS,
            $args
        );
    }


    // Reseller

    public function getResellersRequest($args = [])
    {
        return $this->_sendRequest(
            APIEndpoints::RESELLERS,
            $args
        );

    }

    /* ==================== END REQUESTS ==================== */


    /* ==================== HELPERS ==================== */

    /**
     * Return true if api object already has token
     *
     * @return bool
     */
    public function checkToken(): bool
    {
        if ($this->token)
            return true;
        return false;
    }

    /**
     * Set debug mode.
     *
     * @param bool|int $debug
     * @return $this
     */
    public function setDebug(bool $debug = self::DEBUG_DISABLED)
    {
        $this->debug = $debug;
        return $this;
    }

    public function clearToken()
    {
        unset($_SESSION[$this->tokenSessionKey]);

        return $this;
    }

    public function setTestMode(bool $testMode = self::TEST_MODE_OFF)
    {
        if ($testMode == self::TEST_MODE_ON)
            $this->url = Configuration::get('api_url_cte_v1beta');
        else
            $this->url = Configuration::get('api_url_v1beta');

        return $this;
    }

    /* ==================== END HELPERS ==================== */

}