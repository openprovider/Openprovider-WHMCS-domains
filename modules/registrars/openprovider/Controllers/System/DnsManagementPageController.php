<?php

namespace OpenProvider\WhmcsRegistrar\Controllers\System;

use WHMCS\ClientArea;
use WHMCS\Authentication\CurrentUser;
use WHMCS\Config\Setting;
use WHMCS\Database\Capsule;
use OpenProvider\API\ApiHelper;
use OpenProvider\API\Domain;
use OpenProvider\WhmcsRegistrar\helpers\DomainFullNameToDomainObject;
use OpenProvider\WhmcsRegistrar\src\Configuration;
use WeDevelopCoffee\wPower\Core\Core;
use WeDevelopCoffee\wPower\Controllers\BaseController;

class DnsManagementPageController extends BaseController
{
    const PAGE_TITLE = 'DNS Management';
    const PAGE_NAME  = 'DNS Management';

    /**
     * @var ApiHelper
     */
    private $apiHelper;

    private DnsController $dnsController;
    private Domain $opDomain;

    public function __construct(Core $core, ApiHelper $apiHelper, DnsController $dnsController, Domain $opDomain)
    {
        parent::__construct($core);
        $this->apiHelper = $apiHelper;
        $this->dnsController = $dnsController;
        $this->opDomain = $opDomain;
    }

    public function show($params)
    {
        $ca = new ClientArea();
        $ca->setPageTitle(self::PAGE_NAME);

        $currentUser = new CurrentUser();
        $authUser = $currentUser->user();
        $selectedClient = $currentUser->client();

        if (!$authUser || !$selectedClient) {
            $this->redirectUserAway();
            return;
        }

        $domainId = (int) ($_GET['domainid'] ?? 0);

        $domain = Capsule::table('tbldomains')
            ->where('id', $domainId)
            ->where('userid', $selectedClient->id)
            ->first();

        if (!$domain) {
            $this->redirectUserAway();
            return;
        }

        $domainObj = DomainFullNameToDomainObject::convert($domain->domain);
        $params['sld'] = $domainObj->name;
        $params['tld'] = $domainObj->extension;

        // Delete records
        if ($_SERVER['REQUEST_METHOD'] === 'POST'
            && ($_POST['op_action'] ?? '') === 'deleteRecord') {

            header('Content-Type: application/json; charset=utf-8');

            $currentUser = new \WHMCS\Authentication\CurrentUser();
            if (!$currentUser->user() || !$currentUser->client()) {
                http_response_code(401);
                echo json_encode(['success' => false, 'error' => 'Not authenticated']);
                return;
            }

            // WHMCS CSRF validation
            try {
                check_token('WHMCS.default', true); 
            } catch (\Throwable $e) {
                http_response_code(403);
                echo json_encode(['success' => false, 'error' => 'Invalid CSRF token']);
                return;
            }

            try {
                $hostname = trim((string)($_POST['hostname'] ?? '')); // "www" or ""
                $type     = strtoupper(trim((string)($_POST['type'] ?? '')));
                $address  = trim((string)($_POST['address'] ?? ''));
                $priority = trim((string)($_POST['priority'] ?? ''));

                if ($type === '' || $address === '') {
                    http_response_code(400);
                    echo json_encode(['success' => false, 'error' => 'Invalid record']);
                    return;
                }
                $opDomain = clone $this->opDomain;
                $opDomain->load([
                    'name'      => $params['sld'],
                    'extension' => $params['tld'],
                ]);

                $rec = [
                    'type'  => $type,
                    'name'  => $hostname,
                    'value' => $address,
                    'ttl'   => \OpenProvider\API\APIConfig::$dnsRecordTtl,
                ];

                if (in_array($type, ['MX','SRV'], true) && $priority !== '' && strtoupper($priority) !== 'N/A') {
                    $rec['prio'] = (int)$priority;
                }

                $this->apiHelper->removeDnsRecord($opDomain, $rec);
                echo json_encode(['success' => true]);
                return;

            } catch (\Exception $e) {
                http_response_code(500);
                echo json_encode([
                    'success' => false,
                    'error' => $e->getMessage()
                ]);
            }
            return; 
        }

        // save DNS records
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($_POST['op_action'])) {
            try {
                check_token('WHMCS.default', true);
            } catch (\Throwable $e) {
                $ca->assign('error', 'Invalid CSRF token');
                goto render_page;
            }

            $params['dnsrecords'] = $this->buildDnsRecordsFromPost();
            $result = $this->dnsController->save($params);

            if (is_array($result) && isset($result['error'])) {
                $ca->assign('error', $result['error']);
            } else {
                header("Location: dnsmanagement.php?domainid={$domainId}&saved=1");
                exit;
            }
        }

        render_page:

        // load DNS records for the domain
        $dnsRecords = $this->dnsController->get($params);

        $ca->assign('dnsrecords', $dnsRecords);
        $ca->assign('domainId', $domainId);

        //  Breadcrumbs 
        $ca->addToBreadCrumb('index.php', \Lang::trans('globalsystemname'));
        $ca->addToBreadCrumb('clientarea.php', \Lang::trans('clientareatitle'));
        $ca->addToBreadCrumb('clientarea.php?action=domains', \Lang::trans('clientareanavdomains'));
        $ca->addToBreadCrumb(
            'clientarea.php?action=domaindetails&id=' . $domainId,
            $domain->domain
        );
        $ca->addToBreadCrumb('dnsmanagement.php', self::PAGE_NAME);

        $ca->initPage();
        $ca->requireLogin();

        // Sidebar
        $primarySidebar = \Menu::primarySidebar('domainView');

        $primarySidebar->getChild('Domain Details Management')
            ->addChild('Overview')
            ->setLabel(\Lang::trans('overview'))
            ->setUri("clientarea.php?action=domaindetails&id={$domainId}")
            ->setOrder(0);

        $primarySidebar->getChild('Domain Details Management')
            ->addChild('Auto Renew')
            ->setLabel(\Lang::trans('domainsautorenew'))
            ->setUri("clientarea.php?action=domaindetails&id={$domainId}#tabAutorenew")
            ->setOrder(10);

        $primarySidebar->getChild('Domain Details Management')
            ->addChild('Nameservers')
            ->setLabel(\Lang::trans('orderservernameservers'))
            ->setUri("clientarea.php?action=domaindetails&id={$domainId}#tabNameservers")
            ->setOrder(20);

        $primarySidebar->getChild('Domain Details Management')
            ->addChild('Addons')
            ->setLabel(\Lang::trans('domainaddons'))
            ->setUri("clientarea.php?action=domaindetails&id={$domainId}#tabAddons")
            ->setOrder(30);

        $primarySidebar->getChild('Domain Details Management')
            ->addChild('Contact Information')
            ->setLabel(\Lang::trans('domaincontactinfo'))
            ->setUri("clientarea.php?action=domaincontacts&domainid={$domainId}")
            ->setOrder(40);

        if (isset($domain) && $domain->dnsmanagement) {
            $primarySidebar->getChild('Domain Details Management')
                ->addChild('DNS Management')
                ->setLabel(\Lang::trans('domaindnsmanagement'))
                ->setUri("dnsmanagement.php?domainid={$domainId}")
                ->setOrder(50);
        }

        $primarySidebar->getChild('Domain Details Management')
            ->addChild('DNSSEC')
            ->setLabel(\Lang::trans('dnssectabname'))
            ->setUri("dnssec.php?domainid={$domainId}")
            ->setOrder(100);

        $activeTheme = Setting::getValue('Template');
        $activeTheme = preg_replace('/[^a-zA-Z0-9_-]/', '', (string) $activeTheme);

        $template = '/modules/registrars/openprovider/includes/templates/dnsmanagement.tpl';

        $templatesDir = ROOTDIR . '/templates';
        $templatesDirReal = realpath($templatesDir);

        if ($templatesDirReal !== false && $activeTheme !== '') {
            $candidate = $templatesDirReal . '/' . $activeTheme . '/dnsmanagement.tpl';
            $resolved = realpath($candidate);

            if ($resolved !== false && str_starts_with($resolved, $templatesDirReal . DIRECTORY_SEPARATOR)) {
                $template = 'dnsmanagement';
            }
        }

        $ca->setTemplate($template);
        $ca->assign('csrfToken', $_SESSION['token'] ?? \WHMCS\Session::get('token') ?? '');
        $ca->output();
    }

    private function buildDnsRecordsFromPost(): array
    {
        $records = [];

        $hosts     = $_POST['dnsrecordhost'] ?? [];
        $types     = $_POST['dnsrecordtype'] ?? [];
        $addresses = $_POST['dnsrecordaddress'] ?? [];
        $priority  = $_POST['dnsrecordpriority'] ?? [];

        foreach ($hosts as $i => $host) {
            $host    = trim((string)$host);
            $type    = strtoupper(trim((string)($types[$i] ?? '')));
            $address = trim((string)($addresses[$i] ?? ''));
            $prio    = trim((string)($priority[$i] ?? ''));

            // Skip completely empty rows
            if ($host === '' && $address === '') {
                continue;
            }

            // Skip invalid records (no type or no address)
            if ($type === '' || $address === '') {
                continue;
            }

            $records[] = [
                'hostname' => $host,
                'type'     => $type,
                'address'  => $address,
                'priority' => $prio,
            ];
        }

        return $records;
    }

    private function redirectUserAway()
    {
        $defaultUrl = Configuration::getServerUrl();

        if (isset($_SERVER['HTTP_REFERER'])) {
            $referer = $_SERVER['HTTP_REFERER'];

            // Only allow redirects within same WHMCS base URL
            if (is_string($referer) && $referer !== '' && strpos($referer, $defaultUrl) === 0) {
                header('Location: ' . $referer);
                return;
            }
        }

        header('Location: ' . $defaultUrl);
    }
}
