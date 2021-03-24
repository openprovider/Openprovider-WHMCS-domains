<?php

namespace OpenProvider\API;

use Openprovider\Api\Rest\Client\Domain\Api\DomainPriceServiceApi;
use Openprovider\Api\Rest\Client\Domain\Api\DomainServiceApi;
use Openprovider\Api\Rest\Client\Auth\Api\AuthApi;
use Openprovider\Api\Rest\Client\Person\Api\CustomerApi;
use Openprovider\Api\Rest\Client\Person\Api\EmailVerificationApi;

class CommandMapping
{
    const COMMAND_MAP_METHOD = 'method';
    const COMMAND_MAP_CLASS  = 'apiClass';

    const COMMAND_MAP = [
        // LOGIN
        'generateAuthTokenRequest' => [
            self::COMMAND_MAP_METHOD => 'login',
            self::COMMAND_MAP_CLASS => AuthApi::class,
        ],

        // CUSTOMER
        'retrieveCustomerRequest' => [
            self::COMMAND_MAP_METHOD => 'getCustomer',
            self::COMMAND_MAP_CLASS => CustomerApi::class,
        ],
        'searchEmailVerificationDomainRequest' => [
            self::COMMAND_MAP_METHOD => 'listDomainEmailVerifications',
            self::COMMAND_MAP_CLASS => EmailVerificationApi::class,
        ],
        'startCustomerEmailVerificationRequest' => [
            self::COMMAND_MAP_METHOD => 'startEmailVerification',
            self::COMMAND_MAP_CLASS => EmailVerificationApi::class,
        ],

        // DOMAINS
        'searchDomainRequest' => [
            self::COMMAND_MAP_METHOD => 'listDomains',
            self::COMMAND_MAP_CLASS => DomainServiceApi::class,
        ],
        'modifyDomainRequest' => [
            self::COMMAND_MAP_METHOD => 'updateDomain',
            self::COMMAND_MAP_CLASS => DomainServiceApi::class,
        ],
        'checkDomainRequest' => [
            self::COMMAND_MAP_METHOD => 'checkDomain',
            self::COMMAND_MAP_CLASS => DomainServiceApi::class,
        ],
        'retrievePriceDomainRequest' => [
            self::COMMAND_MAP_METHOD => 'getPrice',
            self::COMMAND_MAP_CLASS => DomainPriceServiceApi::class,
        ],
    ];

    /**
     * @param string $command
     * @param string $field
     * @return string
     */
    public function getCommandMapping(string $command, string $field): string
    {
        if (!isset(self::COMMAND_MAP[$command][$field])) {
            return '';
        }

        return self::COMMAND_MAP[$command][$field];
    }
}
