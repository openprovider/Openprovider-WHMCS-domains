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
            $args = $this->convertArgsToValid($args);
            $reply = $this->xmlApi->sendRequest($cmd, $args);
            if (isset($reply['total'])) {
                $response->setTotal($reply['total'] ?? 0);
                unset($reply['total']);
            }
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
            'test_mode' => $this->configuration->getHost() == Configuration::get('restapi_url_sandbox') ? 'on' : 'off',
        ];

        $debug = $this->configuration->getDebug() ? 1 : 0;
        $this->xmlApi->setParams($params, $debug);
    }

    private function convertArgsToValid(array $args): array
    {
        $result = [];
        foreach ($args as $key => $arg) {
            if (is_array($arg)) {
                $result[$key] = $this->convertArgsToValid($arg);
                continue;
            }
            if (is_bool($arg)) {
                $result[$key] = $arg ? 1 : 0;
                continue;
            }
            $result[$key] = $arg;
        }

        return $result;
    }
}
