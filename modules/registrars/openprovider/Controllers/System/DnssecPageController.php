<?php

namespace OpenProvider\WhmcsRegistrar\Controllers\System;

use WHMCS\ClientArea;
use WHMCS\User\Client;
use WHMCS\Domain\Domain;
use OpenProvider\API\APIConfig;
use OpenProvider\WhmcsRegistrar\helpers\DomainFullNameToDomainObject;
use OpenProvider\WhmcsRegistrar\src\Configuration;
use OpenProvider\API\ApiHelper;
use WeDevelopCoffee\wPower\Core\Core;
use WeDevelopCoffee\wPower\Controllers\BaseController;

/**
 * Class DnssecPageController
 * @package OpenProvider\WhmcsRegistrar\Controllers\System
 */
class DnssecPageController extends BaseController
{
    const PAGE_TITLE  = 'DNSSEC Records';
    const PAGE_NAME   = 'DNSSEC Records';
    const MODULE_NAME = 'dnssec';

    /**
     * @var ApiHelper
     */
    private $apiHelper;

    /**
     * ConfigController constructor.
     */
    public function __construct(Core $core, ApiHelper $apiHelper)
    {
        parent::__construct($core);

        $this->apiHelper = $apiHelper;
    }

    public function show($params)
    {
        $currentUser = Client::find($_SESSION['uid']); 
        if (isset($_GET['domainid'])) {
            $domain = Domain::find($_GET['domainid']);
        }
        
        $ca = new ClientArea();

        $ca->setPageTitle(self::PAGE_NAME);

        $ca->assign('dnssecKeys', $dnssecKeys);
        $ca->assign('isDnssecEnabled', $isDnssecEnabled);
        $ca->assign('apiUrlUpdateDnssecRecords', Configuration::getApiUrl('dnssec-record-update'));
        $ca->assign('apiUrlTurnOnOffDnssec', Configuration::getApiUrl('dnssec-enabled-update'));
        $ca->assign('domainId', $domain['id']);
        $ca->assign('jsModuleUrl', Configuration::getJsModuleUrl(self::MODULE_NAME));
        $ca->assign('cssModuleUrl', Configuration::getCssModuleUrl(self::MODULE_NAME));

        $ca->addToBreadCrumb('index.php', \Lang::trans('globalsystemname'));
        $ca->addToBreadCrumb('clientarea.php', \Lang::trans('clientareatitle'));
        $ca->addToBreadCrumb('clientarea.php?action=domains', \Lang::trans('clientareanavdomains'));
        $ca->addToBreadCrumb('clientarea.php?action=domaindetails&id=' . $domain['id'], $domain['domain']);
        $ca->addToBreadCrumb('dnssec.php', self::PAGE_NAME);

        $ca->initPage();

        $ca->requireLogin();

        if ($domain['userid'] != $currentUser['id']) {
            $this->redirectUserAway();
            return;
        }

        $domainObj = DomainFullNameToDomainObject::convert($domain->domain);

        try {
            $domainOp = $this->apiHelper->getDomain($domainObj);
            $dnssecKeys = $domainOp['dnssecKeys'];
            $isDnssecEnabled = $domainOp['isDnssecEnabled'];

            $openproviderNameserversCount = 0;
            foreach ($domainOp['nameServers'] as $nameServer) {
                if (!in_array($nameServer['name'], APIConfig::getDefaultNameservers())) {
                    continue;
                }
                $openproviderNameserversCount++;
            }
        } catch (\Exception $e) {
            $this->redirectUserAway();
            return;
        }

        \Menu::addContext('client', $currentUser);
        \Menu::addContext('domain', $domain);

        \Menu::primarySidebar('domainView');
        \Menu::secondarySidebar('domainView');

        $ca->setTemplate('/modules/registrars/openprovider/includes/templates/dnssec.tpl');

        $ca->output();
    }

    private function redirectUserAway()
    {
        if (isset($_SERVER["HTTP_REFERER"])) {
            header("Location: " . $_SERVER["HTTP_REFERER"]);
            return;
        }

        header("Location: " . Configuration::getServerUrl());
    }
}
