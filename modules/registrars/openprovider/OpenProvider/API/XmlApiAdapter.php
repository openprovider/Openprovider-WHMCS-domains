<?php

namespace OpenProvider\API;

use Exception;

class XmlApiAdapter implements ApiInterface
{
    /**
     * @var API
     */
    private $xmlApi;

    /**
     * XmlApiAdapter constructor.
     * @param API $xmlApi
     */
    public function __construct(API $xmlApi)
    {
        $this->xmlApi = $xmlApi;
    }

    /**
     * @param string $cmd
     * @param array $args
     * @return Response
     * @throws Exception
     */
    public function call(string $cmd, array $args = []): Response
    {
        $response = new Response();
        try {
            $reply = $this->xmlApi->sendRequest($cmd, $args);
            $response->setTotal($reply['total'] ?? 0);
            unset($reply['total']);
            $response->setData($reply);
        } catch (Exception $e) {
            $response->setCode($e->getCode());
            $response->setMessage($e->getMessage());
        }

        return $response;
    }
}
