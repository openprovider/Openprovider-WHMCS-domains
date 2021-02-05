<?php

return [
    // Set default client if domain has clientid that not exist in WHMCS
    'DEFAULT_CLIENT_ID' => '',

    // Set default contact if domain has contactid that not exist in WHMCS
    'DEFAULT_CONTACT_ID' => '',

    // Choose statuses to domain import
    'DOMAIN_STATUSES_TO_IMPORT' => [
        'DOMAIN_STATUS_ACTIVE',
        'DOMAIN_STATUS_QUARANTINE',
        // 'DOMAIN_STATUS_REGISTERED_ELSEWHERE', ...
    ],

    'DEFAULT_PAYMENT_METHOD' => 'mailin', // Or 'paypal'

    'NEXT_DUE_DATE_OFFSET_FROM_EXPIRY_DATE' => 2,

    'CURRENCY_CODE' => '' // USD, EUR, etc... If empty - script get default currency
];
