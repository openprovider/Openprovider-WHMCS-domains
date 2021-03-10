<?php

namespace OpenProvider\API;

use OpenProvider\WhmcsRegistrar\src\Configuration;

class XmlApiAdapter implements ApiCallerConfigurationAwareInterface
{
    /**
     * @var API
     */
    private API $xmlApi;

    /**
     * @var ApiConfiguration
     */
    private ApiConfiguration $config;

    /**
     * XmlApiAdapter constructor.
     * @param API $xmlApi
     * @param ApiConfiguration $config
     */
    public function __construct(API $xmlApi, ApiConfiguration $config)
    {
        $this->xmlApi = $xmlApi;
        $this->config = $config;
    }

    /**
     * @param string $cmd
     * @param array $args
     * @return Response
     */
    public function call(string $cmd, array $args = []): Response
    {
        $response = new Response();
        $this->setXmlApiConfig();
        try {
            $reply = $this->xmlApi->sendRequest($cmd, $args);
            $response->setTotal($reply['total'] ?? 0);
            unset($reply['total']);
            $response->setData($reply);
        } catch (\Exception $e) {
            $response->setCode($e->getCode());
            $response->setMessage($e->getMessage());
        }

        return $response;
    }

    /**
     * @return ConfigurationInterface
     */
    public function getConfig(): ConfigurationInterface
    {
        return $this->config;
    }

    /**
     *
     */
    private function setXmlApiConfig(): void
    {
        $params = [
            'username' => $this->config->getUserName(),
            'password' => $this->config->getPassword(),
            'test_mode' => $this->config->getHost() == Configuration::get('api_url_cte')
                ? 'on'
                : 'off',
        ];

        $debug = $this->config->getDebug() ? 1 : 0;
        $this->xmlApi->setParams($params, $debug);
    }
}
