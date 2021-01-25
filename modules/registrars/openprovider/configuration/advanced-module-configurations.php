<?php

return [
    'api_url'                           => 'https://api.openprovider.eu/',
    'api_url_cte'                       => 'https://api.cte.openprovider.eu/',
    'OpenproviderPremium'               => false, //  Default: false, Support premium domains
    'require_op_dns_servers'            => true,  //  Default: true,  Require Openprovider DNS servers for DNS management
    'syncUseNativeWHMCS'                => true,  //  Default: true,  Use the native WHMCS synchronisation?
    'syncDomainStatus'                  => true,  //  Default: true,  Synchronize Domain status from Openprovider?
    'syncAutoRenewSetting'              => true,  //  Default: true,  Synchronize Auto renew setting to Openprovider?
    'syncIdentityProtectionToggle'      => true,  //  Default: true,  Synchronize Identity protection to Openprovider?
    'syncExpiryDate'                    => true,  //  Default: true,  Synchronize Expiry date from Openprovider?
    'updateNextDueDate'                 => false, //  Default: false, Synchronize due-date with offset?
    'nextDueDateOffset'                 => 14,    //  Default: 14,    Due-date offset
    'nextDueDateUpdateMaxDayDifference' => 100,   //  Default: 100,   Due-date max difference in days
    'updateInterval'                    => 2,     //  Default: 2,     Update interval
    'domainProcessingLimit'             => 200,   //  Default: 200,   Domain process limit
    'sendEmptyActivityEmail'            => false, //  Default: false, Send empty activity reports?
    'renewTldsUponTransferCompletion'   => '',    //  Default: '',    Renew domains upon transfer completion
    'useNewDnsManagerFeature'           => false, //  Default: false, Use new DNS feature?
    'dnsTemplate'                       => '',    //  Default: '',    Dns template
];
