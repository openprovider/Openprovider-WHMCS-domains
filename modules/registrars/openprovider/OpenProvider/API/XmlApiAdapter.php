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
            $sortedResponseData = $this->sortResponseData($reply);
            $response->setData($sortedResponseData['data']);
            $response->setTotal($sortedResponseData['total']);
        } catch (Exception $e) {
            $response->setCode($e->getCode());
            $response->setMessage($e->getMessage());
        }

        return $response;
    }

    /**
     * @param array $reply
     * @return array
     */
    private function sortResponseData(array $reply): array
    {
        $data = [
            'data' => [],
            'total' => 0,
        ];

        if (count($reply) == 0) {
            return $data;
        }

        foreach ($reply as $key => $value) {
            switch ($key) {
                case 'total':
                    $data['total'] = $value;
                    break;
                default:
                    $data['data'][$key] = $value;
                    break;
            }
        }

        return $data;
    }
}
