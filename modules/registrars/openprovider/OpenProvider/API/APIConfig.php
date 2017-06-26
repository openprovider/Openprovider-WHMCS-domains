<?php

namespace OpenProvider\API;

class APIConfig
{
    static public $moduleVersion        =   'whmcs-2.2.4';
    static public $supportedDnsTypes    =   array('A', 'AAAA', 'CNAME', 'MX', 'SPF', 'TXT');
    static public $dnsRecordTtl         =   86400; 
    static public $dnsRecordPriority    =   10; 
    static public $autoRenew            =   'on';
    static public $encoding             =   'UTF-8';
    static public $curlTimeout          =   1000;
    static public $defaultGender        =   \OpenProvider\API\CustomerGender::MALE;
    static public $handlesNames         =   array
    (
        'ownerHandle'                   =>  'Owner',
        'billingHandle'                 =>  'Billing',
        'adminHandle'                   =>  'Admin',
        'techHandle'                    =>  'Tech',
        'resellerHandle'                =>  'Reseller'
    );
}
