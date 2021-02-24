<?php

namespace OpenProvider\API;

use Openprovider\Api\Rest\Client\Base\Configuration as BaseConfiguration;
use Openprovider\Api\Rest\Client\Client as ClientService;
use Openprovider\Api\Rest\Client\Auth\Model\AuthLoginRequest;
use GuzzleHttp6\Client as HttpClient;
use OpenProvider\WhmcsRegistrar\src\Configuration;

class APIV1 implements APIInterface
{

    private $username;
    private $password;
    private $token;
    private $httpClient;
    private $configuration;
    private $clientService;

    public function __construct()
    {
        $this->configuration = new BaseConfiguration();
        $this->httpClient = new HttpClient();
    }

    public function sendRequest($method, $args = [])
    {
        
    }

    public function setHttpClient($httpClient)
    {
        $this->httpClient = $httpClient;
    }

    public function setConfiguration($configuration)
    {
        $this->configuration = $configuration;
    }

    public function setParams($params = null, $debug = 0)
    {
        if (!$params) {
            return;
        }

        session_start();

        if (!isset($params['Username']) || empty($params['Username'])) {
            return;
        }

        if (!isset($params['Password']) || empty($params['Password'])) {
            return;
        }

        $this->username = $params['Username'];
        $this->password = $params['Password'];
        $this->debug = $debug;

        if (isset($params['test_mode']) && $params['test_mode'] == 'on') {
            $this->configuration->setHost(Configuration::get('api_url_cte'));
        } else {
            $this->configuration->setHost(Configuration::get('api_url'));
        }

        $this->initServices();

        $tokenSessionName = md5(
            "token-{$this->configuration->getHost()}-"
            . "{$this->username}-"
            . "{$this->password}"
        );

        if (!isset($_SESSION[$tokenSessionName]) || empty($_SESSION[$tokenSessionName])) {
            try {
                $loginReply = $this->loginRequest();
                $_SESSION[$tokenSessionName] = $loginReply['token'];
            } catch (Exception $ex) {
                throw new Exception("Can not get token.");
            }
        }
        $this->configuration->setAccessToken($_SESSION[$tokenSessionName]);
    }

    private function initServices()
    {
        $this->clientService = new ClientService(
            $this->httpClient,
            $this->configuration
        );
    }

    private function loginRequest()
    {
        $reply = $this->clientService->getAuthModule()->getAuthApi()->login(
            new AuthLoginRequest([
                'username' => $this->username,
                'password' => $this->password
                ])
        );

        return $reply->getData();
    }
}
