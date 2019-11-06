<?php
/**
 * OpenProvider Registrar module
 *
 * @copyright Copyright (c) Openprovider 2018
 */

/**
 * Return the additional fields for OpenProvider.
 *
 * @return void
 */
function openprovider_additional_fields()
{
    $core = openprovider_registrar_core('admin');
    $core->launch();
    $additionalFields = $core->launcher->get(\OpenProvider\WhmcsRegistrar\src\AdditionalFields::class);
    return $additionalFields->get();
}
