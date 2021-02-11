<?php
/**
* Openprovider only overrides additional fields that are configured in WHMCS Domain Pricing with Openprovider as registrar.
* Put this code above the other fields. Don't override additional fields manually for Openprovider: we maintain this for you.
*/

if (function_exists('openprovider_additional_fields'))
    $additionaldomainfields = openprovider_additional_fields();