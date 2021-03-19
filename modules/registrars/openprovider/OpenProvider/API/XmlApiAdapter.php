<?php

namespace OpenProvider\API;

use OpenProvider\WhmcsRegistrar\src\Configuration;

class XmlApiAdapter implements ApiInterface
{
    /**
     * @var API
     */
    private API $xmlApi;

    /**
     * @var ApiConfiguration
     */
    private ApiConfiguration $configuration;

    /**
     * XmlApiAdapter constructor.
     * @param API $xmlApi
     * @param ApiConfiguration $configuration
     */
    public function __construct(API $xmlApi = null, ApiConfiguration $configuration = null)
    {
        $this->xmlApi = $xmlApi ?? new API();
        $this->configuration = $configuration ?? new ApiConfiguration();
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
     * @return ApiConfiguration
     */
    public function getConfiguration(): ApiConfiguration
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
