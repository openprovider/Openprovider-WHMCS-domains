<?php

namespace OpenProvider\API;

use OpenProvider\WhmcsRegistrar\src\Configuration;

class XmlApiAdapter implements ApiInterface
{
    /**
     * @var API
     */
    private $xmlApi;

    /**
     * @var ApiConfiguration
     */
    private $configuration;

    /**
     * XmlApiAdapter constructor.
     * @param API $xmlApi
     */
    public function __construct(API $xmlApi)
    {
        $this->xmlApi = $xmlApi;

        $this->configuration = new ApiConfiguration();
    }

    /**
     * @param string $cmd
     * @param array $args
     * @return ResponseInterface
     */
    public function call(string $cmd, array $args = []): ResponseInterface
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
     * @return ApiConfiguration
     */
    public function getConfiguration(): ConfigurationInterface
    {
        return $this->configuration;
    }

    /**
     * @return void
     */
    private function setXmlApiConfig(): void
    {
        $params = [
            'Username' => $this->configuration->getUserName(),
            'Password' => $this->configuration->getPassword(),
            'test_mode' => $this->configuration->getHost() == Configuration::get('api_url_cte') ? 'on' : 'off',
        ];

        $debug = $this->configuration->getDebug() ? 1 : 0;
        $this->xmlApi->setParams($params, $debug);
    }
}
