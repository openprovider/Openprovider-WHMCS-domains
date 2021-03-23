<?php

namespace OpenProvider\WhmcsRegistrar\helpers;

use Psr\Log\LoggerInterface;

class Logger implements LoggerInterface
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

    /**
     * @param string $message
     * @param array $context
     */
    public function emergency($message, array $context = array())
    {
        $this->log('', $message, $context);
    }

    /**
     * @param string $message
     * @param array $context
     */
    public function alert($message, array $context = array())
    {
        $this->log('', $message, $context);
    }

    /**
     * @param string $message
     * @param array $context
     */
    public function critical($message, array $context = array())
    {
        $this->log('', $message, $context);
    }

    /**
     * @param string $message
     * @param array $context
     */
    public function error($message, array $context = array())
    {
        $this->log('', $message, $context);
    }

    /**
     * @param string $message
     * @param array $context
     */
    public function warning($message, array $context = array())
    {
        $this->log('', $message, $context);
    }

    /**
     * @param string $message
     * @param array $context
     */
    public function notice($message, array $context = array())
    {
        $this->log('', $message, $context);
    }

        /**
     * @param string $message
     * @param array $context
     */
    public function info($message, array $context = array())
    {
        $this->log('', $message, $context);
    }

    /**
     * @param string $message
     * @param array $context
     */
    public function debug($message, array $context = array())
    {
        $this->log('', $message, $context);
    }
}
