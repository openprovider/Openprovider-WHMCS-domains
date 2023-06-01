<?php
/**
 * OpenProvider Registrar module
 *
 * @copyright Copyright (c) Openprovider 2018
 */
use WHMCS\Database\Capsule;

require_once '..'.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'init.php';
require_once dirname(__FILE__).DIRECTORY_SEPARATOR.'openprovider.php';

ob_clean();

if(!isset($_REQUEST['domain']))
{
    echo '\OpenProvider\Domain is not set';
    exit;
}

$params =   array();

try {
    $settings = Capsule::table('tblregistrars')
        ->where('registrar', 'openprovider')->get();

    foreach($settings as $setting)
    {
        $params[$setting->setting]   =   decrypt($setting->value);
    }
} catch (\Exception $e) {
    logActivity('Could not fetch the registrar settings: '.$e->getMessage());
}

try
{
    $d  =   explode('.', $_REQUEST['domain'], 2);
    $api = new \OpenProvider\API\API();
    $api->setParams($params);

    $domain = new \OpenProvider\API\Domain();
    $domain->extension = $d[1];
    $domain->name = $d[0];

    $status =   $api->checkDomain($domain); 

    if(isset($status[0]))
    {
        echo $status[0]['status'];
    }
}
catch (\Exception $e)
{
    logActivity('Openprovider whois: '.$e->getMessage());
}

