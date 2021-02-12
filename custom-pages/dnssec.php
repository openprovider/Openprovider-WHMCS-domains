<?php

use OpenProvider\WhmcsRegistrar\helpers\DB;
use WHMCS\ClientArea;
use OpenProvider\WhmcsRegistrar\src\Configuration;
use OpenProvider\WhmcsRegistrar\src\OpenProvider;
use OpenProvider\WhmcsRegistrar\helpers\Language;

define('CLIENTAREA', true);

const MODULE_NAME = 'dnssec';

require __DIR__ . '/init.php';

$ca = new ClientArea();

// Get system language and content
Language::setLang(DB::getSystemLanguage());
$lang = Language::getContent(Language::PAGE_DNSSEC);

$ca->setPageTitle($lang['page_name']);

$domainId = $_GET['domainid'];
$domain = \WHMCS\Database\Capsule::table('tbldomains')
    ->where('id', $domainId)
    ->first();

if (!$domain->dnsmanagement && isset($_SERVER["HTTP_REFERER"])) {
    header("Location: " . $_SERVER["HTTP_REFERER"]);
}
elseif (!$domain->dnsmanagement) {
    header("Location: " . '/whmcs/');
}

$domainName = $domain->domain;

$OpenProvider = new OpenProvider();
$api = $OpenProvider->api;

$domainArray = explode('.', $domainName);
$args = [
    'domain' => [
        'extension' => $domainArray[count($domainArray) - 1],
        'name' => implode('.', array_slice($domainArray, 0, count($domainArray) - 1)),
    ],
];

$dnssecKeys = [];
$isDnssecEnabled = false;
try {
    $domain = $api->sendRequest('retrieveDomainRequest', $args);
    $dnssecKeys = $domain['dnssecKeys'];
    $isDnssecEnabled = $domain['isDnssecEnabled'];
} catch (\Exception $e) {
    header("Location: " . $_SERVER["HTTP_REFERER"]);
}

$ca->assign('dnssecKeys', $dnssecKeys);
$ca->assign('isDnssecEnabled', $isDnssecEnabled);
$ca->assign('apiUrlUpdateDnssecRecords', Configuration::getApiUrl('dnssec-record-update'));
$ca->assign('apiUrlTurnOnOffDnssec', Configuration::getApiUrl('dnssec-enabled-update'));
$ca->assign('domainId', $domainId);
$ca->assign('jsModuleUrl', Configuration::getJsModuleUrl(MODULE_NAME));
$ca->assign('cssModuleUrl', Configuration::getCssModuleUrl(MODULE_NAME));
$ca->assign('lang', $lang);

$ca->addToBreadCrumb('index.php', Lang::trans('globalsystemname'));
$ca->addToBreadCrumb('clientarea.php', Lang::trans('clientareatitle'));
$ca->addToBreadCrumb('clientarea.php?action=domains', Lang::trans('clientareanavdomains'));
$ca->addToBreadCrumb('clientarea.php?action=domaindetails&id=' . $domainId, $domainName);
$ca->addToBreadCrumb('dnssec.php', $lang['page_name']);

$ca->initPage();

$ca->requireLogin();

$primarySidebar = Menu::primarySidebar('domainView');

$primarySidebar->getChild('Domain Details Management')
    ->addChild('Overview')
    ->setLabel('Overview')
    ->setUri("clientarea.php?action=domaindetails&id={$domainId}")
    ->setOrder(0);

$primarySidebar->getChild('Domain Details Management')
    ->addChild('Auto Renew')
    ->setLabel('Auto Renew')
    ->setUri("clientarea.php?action=domaindetails&id={$domainId}#tabAutorenew")
    ->setOrder(10);

$primarySidebar->getChild('Domain Details Management')
    ->addChild('Nameservers')
    ->setLabel('Nameservers')
    ->setUri("clientarea.php?action=domaindetails&id={$domainId}#tabNameservers")
    ->setOrder(20);

$primarySidebar->getChild('Domain Details Management')
    ->addChild('Addons')
    ->setLabel('Addons')
    ->setUri("clientarea.php?action=domaindetails&id={$domainId}#tabAddons")
    ->setOrder(30);

$primarySidebar->getChild('Domain Details Management')
    ->addChild('Contact Information')
    ->setLabel('Contact Information')
    ->setUri("clientarea.php?action=domaincontacts&domainid={$domainId}")
    ->setOrder(40);

$primarySidebar->getChild('Domain Details Management')
    ->addChild('DNS Management')
    ->setLabel('DNS Management')
    ->setUri("clientarea.php?action=domaindns&domainid={$domainId}")
    ->setOrder(50);

$ca->setTemplate('/modules/registrars/openprovider/includes/templates/dnssec.tpl');

$ca->output();
