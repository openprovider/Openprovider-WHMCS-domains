<?php

namespace OpenProvider\API;

use Openprovider\Api\Rest\Client\Domain\Api\DomainServiceApi;
use Openprovider\Api\Rest\Client\Auth\Api\AuthApi;

class CommandMapping
{
    const COMMAND_MAP_METHOD = 'method';
    const COMMAND_MAP_CLASS  = 'apiClass';
    const COMMAND_MAP_PARAMETERS_TYPE = 'paramsType';

    const PARAMS_TYPE_BODY = 'body';
    const PARAMS_TYPE_VIA_COMMA = 'comma';

    /**
     * @param string $command
     * @param string $field
     * @return string
     */
    public function getCommandMapping(string $command, string $field): string
    {
        if (!isset(self::COMMAND_MAP[$command][$field])) {
            return false;
        }

        return self::COMMAND_MAP[$command][$field];
    }

    const COMMAND_MAP = [
        'searchDomainRequest' => [
            self::COMMAND_MAP_METHOD => 'listDomains',
            self::COMMAND_MAP_CLASS => DomainServiceApi::class,
            self::COMMAND_MAP_PARAMETERS_TYPE => self::PARAMS_TYPE_VIA_COMMA
        ],
        'generateAuthTokenRequest' => [
            self::COMMAND_MAP_METHOD => 'login',
            self::COMMAND_MAP_CLASS => AuthApi::class,
            self::COMMAND_MAP_PARAMETERS_TYPE => self::PARAMS_TYPE_BODY
        ],
    ];

}
