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
        $replaceVars = isset($context['request']['password']) ? [
            $context['request']['password'],
            htmlentities($context['request']['password'])
        ] : [];

        if (isset($context['response']['data']['token'])) {
            $replaceVars[] = $context['response']['data']['token'];
        }

        logModuleCall(
            self::MODULE_NAME,
            $message,
            json_encode($context['request']),
            json_encode($context['response']),
            null,
            $replaceVars
        );
    }
}
