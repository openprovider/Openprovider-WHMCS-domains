<?php

namespace OpenProvider;

use Psr\Log\AbstractLogger;

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
            json_encode($context['request']),
            json_encode($context['response'])
        );
    }
}
