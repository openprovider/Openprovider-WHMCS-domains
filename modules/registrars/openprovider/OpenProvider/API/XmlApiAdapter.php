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
        $apiResponse = new Response();
        try {
            $reply = $this->xmlApi->sendRequest($cmd, $args);
            $apiResponse->setData($reply['data']);
            $apiResponse->setTotal($reply['total']);
        } catch (Exception $ex) {
            $apiResponse->setCode($ex->getCode());
            $apiResponse->setMessage($ex->getMessage());
        }

        return $apiResponse;
    }
}
