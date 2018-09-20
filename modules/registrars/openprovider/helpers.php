<?php
/**
 * OpenProvider Registrar module
 *
 * @copyright Copyright (c) Openprovider 2018
 */

if (!defined("WHMCS"))
{
    die("This file cannot be accessed directly");
}

/**
 * Return the additional fields for OpenProvider.
 *
 * @return void
 */
function openprovider_additional_fields()
{
    $additionalFields = wLaunch(OpenProvider\WhmcsRegistrar\Library\AdditionalFields::class);
    return $additionalFields->get();
}
