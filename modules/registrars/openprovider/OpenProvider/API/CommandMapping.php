<?php

namespace OpenProvider\API;

use Openprovider\Api\Rest\Client\Dns\Api\NameserverServiceApi;
use Openprovider\Api\Rest\Client\Dns\Api\ZoneServiceApi;
use Openprovider\Api\Rest\Client\Domain\Api\DomainPriceServiceApi;
use Openprovider\Api\Rest\Client\Domain\Api\DomainServiceApi;
use Openprovider\Api\Rest\Client\Auth\Api\AuthApi;
use Openprovider\Api\Rest\Client\Helpers\Api\TagServiceApi;
use Openprovider\Api\Rest\Client\Person\Api\ContactServiceApi;
use Openprovider\Api\Rest\Client\Person\Api\CustomerApi;
use Openprovider\Api\Rest\Client\Person\Api\EmailVerificationApi;
use Openprovider\Api\Rest\Client\Person\Api\PromoMessageServiceApi;
use Openprovider\Api\Rest\Client\Person\Api\ResellerServiceApi;
use Openprovider\Api\Rest\Client\Tld\Api\TldServiceApi;

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
        'createCustomerRequest' => [
            self::COMMAND_MAP_METHOD => 'createCustomer',
            self::COMMAND_MAP_CLASS => CustomerApi::class,
        ],
        'modifyCustomerRequest' => [
            self::COMMAND_MAP_METHOD => 'updateCustomer',
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
        'restartCustomerEmailVerificationRequest' => [
            self::COMMAND_MAP_METHOD => 'restartEmailVerification',
            self::COMMAND_MAP_CLASS => EmailVerificationApi::class,
        ],

        // CONTACTS
        'searchContactRequest' => [
            self::COMMAND_MAP_METHOD => 'listContacts',
            self::COMMAND_MAP_CLASS => ContactServiceApi::class
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
        'createDomainRequest' => [
            self::COMMAND_MAP_METHOD => 'createDomain',
            self::COMMAND_MAP_CLASS => DomainServiceApi::class,
        ],
        'transferDomainRequest' => [
            self::COMMAND_MAP_METHOD => 'transferDomain',
            self::COMMAND_MAP_CLASS => DomainServiceApi::class,
        ],
        'checkDomainRequest' => [
            self::COMMAND_MAP_METHOD => 'checkDomain',
            self::COMMAND_MAP_CLASS => DomainServiceApi::class,
        ],
        'restoreDomainRequest' => [
            self::COMMAND_MAP_METHOD => 'restoreDomain',
            self::COMMAND_MAP_CLASS => DomainServiceApi::class,
        ],
        'renewDomainRequest' => [
            self::COMMAND_MAP_METHOD => 'renewDomain',
            self::COMMAND_MAP_CLASS => DomainServiceApi::class,
        ],
        'deleteDomainRequest' => [
            self::COMMAND_MAP_METHOD => 'deleteDomain',
            self::COMMAND_MAP_CLASS => DomainServiceApi::class,
        ],
        'suggestNameDomainRequest' => [
            self::COMMAND_MAP_METHOD => 'suggestNameDomain',
            self::COMMAND_MAP_CLASS => DomainServiceApi::class
        ],
        'retrievePriceDomainRequest' => [
            self::COMMAND_MAP_METHOD => 'getPrice',
            self::COMMAND_MAP_CLASS => DomainPriceServiceApi::class,
        ],

        // TLDS
        'searchExtensionRequest' => [
            self::COMMAND_MAP_METHOD => 'listTlds',
            self::COMMAND_MAP_CLASS => TldServiceApi::class,
        ],
        'retrieveExtensionRequest' => [
            self::COMMAND_MAP_METHOD => 'getTld',
            self::COMMAND_MAP_CLASS => TldServiceApi::class,
        ],


        // DNS
        'retrieveZoneDnsRequest' => [
            self::COMMAND_MAP_METHOD => 'getZone',
            self::COMMAND_MAP_CLASS => ZoneServiceApi::class,
        ],
        'modifyZoneDnsRequest' => [
            self::COMMAND_MAP_METHOD => 'updateZone',
            self::COMMAND_MAP_CLASS => ZoneServiceApi::class,
        ],
        'createZoneDnsRequest' => [
            self::COMMAND_MAP_METHOD => 'createZone',
            self::COMMAND_MAP_CLASS => ZoneServiceApi::class,
        ],
        'deleteZoneDnsRequest' => [
            self::COMMAND_MAP_METHOD => 'createZone',
            self::COMMAND_MAP_CLASS => ZoneServiceApi::class,
        ],

        // Nameservers
        'createNsRequest' => [
            self::COMMAND_MAP_METHOD => 'createNameserver',
            self::COMMAND_MAP_CLASS => NameserverServiceApi::class,
        ],
        'modifyNsRequest' => [
            self::COMMAND_MAP_METHOD => 'updateNameserver',
            self::COMMAND_MAP_CLASS => NameserverServiceApi::class,
        ],
        'deleteNsRequest' => [
            self::COMMAND_MAP_METHOD => 'deleteNameserver',
            self::COMMAND_MAP_CLASS => NameserverServiceApi::class,
        ],

        // Helpers
        'searchTagRequest' => [
            self::COMMAND_MAP_METHOD => 'listTags',
            self::COMMAND_MAP_CLASS => TagServiceApi::class,
        ],

        // Reseller
        'retrieveResellerRequest' => [
            self::COMMAND_MAP_METHOD => 'getReseller',
            self::COMMAND_MAP_CLASS  => ResellerServiceApi::class
        ],

        // Messages
        'searchPromoMessageRequest' => [
            self::COMMAND_MAP_METHOD => 'listPromoMessages',
            self::COMMAND_MAP_CLASS => PromoMessageServiceApi::class
        ]
    ];

    /**
     * @param string $command
     * @param string $field
     * @return string
     * @throws \Exception
     */
    public function getCommandMapping(string $command, string $field): string
    {
        if (!isset(self::COMMAND_MAP[$command][$field])) {
            throw new \Exception("Field {$field} not found into command mapping!");
        }

        return self::COMMAND_MAP[$command][$field];
    }
}
