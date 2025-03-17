<?php

return [
    //Openprovider Production and CTE API endpoints
    'api_url'                           => 'https://api.openprovider.eu',
    'api_url_cte'                       => 'http://api.sandbox.openprovider.nl:8480',

    //  Default: false, boolean - Set to true to allow support for premium domains
    'OpenproviderPremium'               => false,
    //  Default: true,  boolean - Set to true to Require Openprovider DNS servers for DNS management
    'require_op_dns_servers'            => true,
    //  Default: '',    string -Enter TLDs split by a comma ("nl,eu,be") The module will alway try to renew TLDs in this list as soon as transfer is completed. This is useful for TLDs which don't include an automatic renewal with domain transfer. Note that this will incur a cost in your Openprovider account
    'renewTldsUponTransferCompletion'   => '',
    //  Default: '', string - Choose a DNS template
    'dnsTemplate'                       => '',
    'useNewDnsManagerFeature'           => false, //  Default: false, Use the Openprovider DNS panel instead of the WHMCS DNS editing page (https://support.openprovider.eu/hc/en-us/articles/360014539999-Single-Domain-DNS-panel)

    //choose which settings will be synched by the openprovider sync task
    'syncAutoRenewSetting'              => true,  //  Default: true,  Synchronize Auto renew setting to Openprovider?
    'syncIdentityProtectionToggle'      => true,  //  Default: true,  Synchronize Identity protection to Openprovider?

    //Openprovider Synchronization settings
    'syncUseNativeWHMCS'                => true,  //  Default: true,  Use the native WHMCS synchronisation
    'syncDomainStatus'                  => true,  //  Default: true,  Synchronize Domain status from Openprovider
    'syncExpiryDate'                    => true,  //  Default: true,  Synchronize Expiry date from Openprovider
    'updateNextDueDate'                 => true,  //  Default: true,  Synchronize due-date with offset?
    'nextDueDateOffset'                 => 14,    //  Default: 14,    Due-date offset
    'nextDueDateUpdateMaxDayDifference' => 100,   //  Default: 100,   Due-date max difference in days
    'updateInterval'                    => 2,     //  Default: 2,     The minimum number of hourse before a domain will be updated
    'domainProcessingLimit'             => 200,   //  Default: 200,   Domain process limit
    'sendEmptyActivityEmail'            => false, //  Default: false, Send empty activity reports?

    // Trustee service
    // any TLDs which are included in the array,
    // for example [“ba”,”co.id”]  will automatically have the trustee option selected upon registration.
    // Note that registering a domain with the trustee service may incur additional fees,
    // please check your Openprovider account for detailed pricing information
    // before activating automatic trustee activation.
    'requestTrusteeService' => [],

    // maxRegistrationPeriod
    'maxRegistrationPeriod' => 1,

    // enable advanced additional data management for .es and .pt domain registrations    
    'idnumbermod' => false,
];
