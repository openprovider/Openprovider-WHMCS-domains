<?php
namespace OpenProvider\API;

/**
 * Class DomainTransfer
 * OpenProvider Registrar module
 *
 * @copyright Copyright (c) Openprovider 2018
 */

class DomainTransfer extends \OpenProvider\API\DomainRegistration
{
    public $authCode;

    /**
     * Ask Openprovider to import contact data from the registry
     * (maps to import_contacts_from_registry on the transfer request).
     *
     * @var bool|null
     */
    public $importContactsFromRegistry = null;
}
