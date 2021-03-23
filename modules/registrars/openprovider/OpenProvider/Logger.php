<?php

namespace OpenProvider;

use Psr\Log\AbstractLogger;
use Psr\Log\LogLevel;

class Logger extends AbstractLogger
{
    private const MODULE_NAME = 'openprovider nl';

    /**
     * @param mixed $level
     * @param string $message
     * @param array $context
     */
    public function log($level, $message, $context = []): void
    {
        logModuleCall(
            self::MODULE_NAME,
            $message,
            [
                'request_body' => json_encode($context['request']),
            ],
            [
                'response_data' => json_encode($context['response']['data']),
                'response_code' => $context['response']['code'],
                'response_total' => $context['response']['total'],
                'response_message' => $context['response']['message'],
            ]
        );
    }
}
