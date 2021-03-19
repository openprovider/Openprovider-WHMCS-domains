<?php

namespace OpenProvider\API;

use Openprovider\Api\Rest\Client\Domain\Api\DomainServiceApi;
use Openprovider\Api\Rest\Client\Auth\Api\AuthApi;
use Openprovider\Api\Rest\Client\Person\Api\EmailVerificationApi;

class CommandMapping
{
    const COMMAND_MAP_METHOD = 'method';
    const COMMAND_MAP_CLASS  = 'apiClass';
    const COMMAND_MAP_PARAMETERS_TYPE = 'paramsType';

    const PARAMS_TYPE_BODY = 'body';
    const PARAMS_TYPE_VIA_COMMA = 'comma';

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
        'searchEmailVerificationDomainRequest' => [
            self::COMMAND_MAP_METHOD => 'listDomainEmailVerifications',
            self::COMMAND_MAP_CLASS => EmailVerificationApi::class,
            self::COMMAND_MAP_PARAMETERS_TYPE => self::PARAMS_TYPE_VIA_COMMA,
        ]
    ];

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
}
