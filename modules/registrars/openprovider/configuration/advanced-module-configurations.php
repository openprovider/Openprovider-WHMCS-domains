<?php

return [
    //Openprovider Production and CTE API endpoints XML format
    'api_url'                           => 'https://api.openprovider.eu/', 
    'api_url_cte'                       => 'https://api.cte.openprovider.eu/', 

    //Openprovider Production and CTE API endpoints JSON format
    'api_url_v1beta'                    => 'https://api.openprovider.eu/v1beta',
    'api_url_cte_v1beta'                => 'https://api.cte.openprovider.eu/v1beta',

    //  Default: false, boolean - Set to true to allow support for premium domains
    'OpenproviderPremium'               => false, 
    //  Default: true,  boolean - Set to true to Require Openprovider DNS servers for DNS management
    'require_op_dns_servers'            => false,  
    //  Default: '',    string -Enter TLDs split by a comma ("nl,eu,be") The module will alway try to renew TLDs in this list as soon as transfer is completed. This is useful for TLDs which don't include an automatic renewal with domain transfer. Note that this will incur a cost in your Openprovider account
    'renewTldsUponTransferCompletion'   => '',    
    //  Default: '', string - Choose a DNS template 
    'dnsTemplate'                       => '',    
    'useNewDnsManagerFeature'           => false, //  Default: false, Use the Openprovider DNS panel instead of the WHMCS DNS editing page (https://support.openprovider.eu/hc/en-us/articles/360014539999-Single-Domain-DNS-panel)
    
    //choose which settings will be synched by the openprovider sync task
    'syncAutoRenewSetting'              => true,  //  Default: true,  Synchronize Auto renew setting to Openprovider?
    'syncIdentityProtectionToggle'      => true,  //  Default: true,  Synchronize Identity protection to Openprovider?
    
    //Openprovid Synchronization settings
    'syncUseNativeWHMCS'                => true,  //  Default: true,  Use the native WHMCS synchronisation
    'syncDomainStatus'                  => true,  //  Default: true,  Synchronize Domain status from Openprovider
    'syncExpiryDate'                    => true,  //  Default: true,  Synchronize Expiry date from Openprovider
    'updateNextDueDate'                 => true, //  Default: true, Synchronize due-date with offset?
    'nextDueDateOffset'                 => 14,    //  Default: 14,    Due-date offset
    'nextDueDateUpdateMaxDayDifference' => 100,   //  Default: 100,   Due-date max difference in days
    'updateInterval'                    => 2,     //  Default: 2,     The minimum number of hourse before a domain will be updated
    'domainProcessingLimit'             => 200,   //  Default: 200,   Domain process limit
    'sendEmptyActivityEmail'            => false, //  Default: false, Send empty activity reports?
];
