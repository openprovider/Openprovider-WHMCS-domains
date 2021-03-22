<?php

namespace OpenProvider\API;

class Logger
{
    private const MODULE_NAME = 'openprovider nl';

    /**
     * @param $cmd
     * @param array $requestData
     * @param Response $response
     */
    public function log($cmd, array $requestData, Response $response): void
    {
        logModuleCall(
            self::MODULE_NAME,
            $cmd,
            [
                'request_body' => json_encode($requestData),
            ],
            [
                'response_data' => json_encode($response->getData()),
                'response_code' => $response->getCode(),
                'response_total' => $response->getTotal(),
                'response_message' => $response->getMessage(),
            ]
        );
    }
}