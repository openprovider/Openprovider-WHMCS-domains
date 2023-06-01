<?php
// example of running this script from console:
// php import.php SSLCertificates.csv

require_once(__DIR__ . '/../../../../../init.php');
require_once(__DIR__ . '/../../openprovider.php');

use Carbon\Carbon;
use WHMCS\Database\Capsule;
use OpenProvider\WhmcsRegistrar\enums\FileOpenModeType;
use OpenProvider\WhmcsRegistrar\enums\ImportStatusType;
use OpenProvider\WhmcsRegistrar\enums\WHMCSApiActionType;
use OpenProvider\WhmcsRegistrar\enums\DatabaseTable;
use OpenProvider\WhmcsRegistrar\helpers\CSV;
use OpenProvider\WhmcsRegistrar\helpers\Debug;
use OpenProvider\OpenProvider;

$sslOrdersFromOpAPI = getSSLOrdersFromOpApi();
$sslOrdersFromCSV = getSSLOrdersByCsv($argv[1]);
addSSLOrdersToWhmcs($sslOrdersFromCSV, $sslOrdersFromOpAPI);

function getSSLOrdersFromOpApi(): array
{
    $openprovider = new OpenProvider();
    Debug::errorLog('Waiting answer from OP API...', Debug::MODULE_IMPORT_SSL);
    $sslOrdersFromOpAPI = $openprovider->api->searchSSL();
    if (empty($sslOrdersFromOpAPI['results'])) {
        Debug::errorLog('You have not any SSL Orders in OP Account or API Error', Debug::MODULE_IMPORT_SSL);
        die;
    }

    return $sslOrdersFromOpAPI['results'];
}

function getSSLOrdersByCsv(string $importFilePath): ?array
{
    $csv = new CSV($importFilePath);
    if (!$csv->open()) {
        Debug::errorLog("File {$importFilePath} open Failed!", Debug::MODULE_IMPORT_SSL);
        die;
    }

    $csv->setHeaders();
    $needleHeaders = [
        'customer_id' => CSV::HeaderRequired,
        'ssl_id' => CSV::HeaderRequired,
        'brand' => CSV::HeaderRequired,
        'product' => CSV::HeaderRequired,
    ];
    $checkHeaders = $csv->checkHeaders($needleHeaders);
    if (!$checkHeaders['success']) {
        $csv->close();
        $missedHeaders = implode(', ', $checkHeaders['missingHeaders']);
        Debug::errorLog(
            'You have no following headers in your csv import file: ' . $missedHeaders,
            Debug::MODULE_IMPORT_SSL
        );
        die;
    }

    $customers = Capsule::table(DatabaseTable::MappingInternalExternalContacts)
        ->where('source_name', 'powerpanel')
        ->where('external_contact_type', 'customer')
        ->where('internal_contact_type', 'client')
        ->get();
    if (count($customers) === 0) {
        Debug::errorLog(
            'No imported PowerPanel customers found. Please run customers import first',
            Debug::MODULE_IMPORT_SSL
        );
        die;
    }

    $orders = $csv->getRecords();
    $csv->close();

    if (count($orders) < 1) {
        Debug::errorLog('Your csv does not contain any ssl records', Debug::MODULE_IMPORT_SSL);
        die;
    }

    return $orders;
}

function addSSLOrdersToWhmcs(array $sslOrdersFromCSV, array $sslOrdersFromAPI): void
{
    $csvReportRowTmpl = [
        'csv_row_number' => null,
        'ssl_id' => null,
        'certificate_type' => null,
        'certificate_name' => null,
        'whmcs_product_id' => null,
        'whmcs_client_id' => null,
        'power_panel_customer_id' => null,
        'status' => ImportStatusType::Imported,
        'message' => null,
    ];

    $reportFilePath = $argv[2] ?? __DIR__ . '/report-' . Carbon::now()->format('Y-m-d-H:i:s') . '.csv';
    $reportCsv = new CSV($reportFilePath, FileOpenModeType::CreateAndWritePlus);
    $reportCsv->setHeaders(array_keys($csvReportRowTmpl));
    if (!$reportCsv->open()) {
        Debug::errorLog('Can not create report file!', Debug::MODULE_IMPORT_SSL);
        return;
    }

    $reportCsv->writeRecords([], CSV::WriteHeaders);
    $allExternalIDs = Capsule::table(DatabaseTable::MappingInternalExternalContacts)->pluck('external_id')->all();
    $mapInternalExternalContacts = Capsule::table(DatabaseTable::MappingInternalExternalContacts)->get()->all();
    $whmcsProducts = Capsule::table(DatabaseTable::Products)->get()->all();

    $counter = 0;
    $successImported = 0;
    foreach ($sslOrdersFromCSV as $sslOrderFromCSV) {
        $counter++;
        echo Debug::progressBar($counter, count($sslOrdersFromCSV));
        $reportRow = addSSLOrder(
            $counter, $sslOrderFromCSV, $allExternalIDs, $sslOrdersFromAPI, $mapInternalExternalContacts,
            $whmcsProducts, $csvReportRowTmpl
        );
        if ($reportRow[7] === ImportStatusType::Imported) {
            $successImported++;
        }
        $reportCsv->writeRecords([$reportRow], CSV::NoWriteHeaders);
    }

    echo PHP_EOL;
    Debug::errorLog(
        'Success imported ' . $successImported . ' of ' . count($sslOrdersFromCSV),
        Debug::MODULE_IMPORT_SSL
    );
    Debug::errorLog(
        'You can check report in ' . $reportCsv->getFilepath(),
        Debug::MODULE_IMPORT_SSL
    );
    $reportCsv->close();
}

function getMatchedOpApiOrder($WhmcsOrder, $OpSslOrders): ?array
{
    foreach ($OpSslOrders as $OpSslOrder) {
        if ($OpSslOrder['id'] == $WhmcsOrder['vendor_certificate_id']) {
            return $OpSslOrder;
        }
    }

    return null;
}

function getMatchedWhmcsProduct($sslOrderFromCSV, $whmcsProducts): ?object
{
    foreach ($whmcsProducts as $whmcsProduct) {
        if ($sslOrderFromCSV['product'] == $whmcsProduct->name) {
            return $whmcsProduct;
        }
    }

    return null;
}

function matchWithCustomer($reportRow, $sslOrderFromCSV, $allExtIDs, $mapInternalExternalContacts)
{
    if (!in_array($sslOrderFromCSV['customer_id'], $allExtIDs)) {
        return $reportRow;
    }

    foreach ($mapInternalExternalContacts as $mapRow) {
        if ($mapRow->external_id == $sslOrderFromCSV['customer_id']) {
            $reportRow['whmcs_client_id'] = $mapRow->client_or_contact_id;
        }
    }

    return $reportRow;
}

function modifyDomainName($domainName): string
{
    $arr = explode('.', $domainName);
    if ($arr[0] == '*') {
        array_shift($arr);
    }
    $newDomainName = implode('.', $arr);

    return $newDomainName;
}

function addSSLOrder(
    $counter, $sslOrderFromCSV, $allExtIDs, $sslOrdersFromAPI, $mapInternalExternalContacts, $whmcsProducts, $csvReportRowTmpl
): array
{
    $reportRow = $csvReportRowTmpl;
    $reportRow['csv_row_number'] = $counter;
    $reportRow['ssl_id'] = $sslOrderFromCSV['ssl_id'];
    $reportRow['certificate_type'] = $sslOrderFromCSV['brand'];
    $reportRow['certificate_name'] = $sslOrderFromCSV['product'];
    $reportRow['power_panel_customer_id'] = $sslOrderFromCSV['customer_id'];

    $reportRow = matchWithCustomer($reportRow, $sslOrderFromCSV, $allExtIDs, $mapInternalExternalContacts);
    if (empty($reportRow['whmcs_client_id'])) {
        $reportRow['status'] = ImportStatusType::NotImported;
        $reportRow['message'] = 'This customer not imported from PowerPanel to Whmcs';

        return array_values($reportRow);
    }

    $whmcsProduct = getMatchedWhmcsProduct($sslOrderFromCSV, $whmcsProducts);
    if (empty($whmcsProduct)) {
        $reportRow['status'] = ImportStatusType::NotImported;
        $reportRow['message'] = 'You are not done SSL products import or we can\'t match product name';

        return array_values($reportRow);
    }
    $reportRow['whmcs_product_id'] = $whmcsProduct->id;

    $OpApiOrder = getMatchedOpApiOrder($sslOrderFromCSV, $sslOrdersFromAPI);
    if (empty($OpApiOrder)) {
        $reportRow['status'] = ImportStatusType::NotImported;
        $reportRow['message'] = 'This SSL Order can not be matched with OP account';

        return array_values($reportRow);
    }
    if ($OpApiOrder['period'] == '1') {
        $billingcycle = 'annually';
    } elseif ($OpApiOrder['period'] == '2') {
        $billingcycle = 'biennially';
    } else {
        $billingcycle = 'annually';
    }
    $domainName = modifyDomainName($OpApiOrder['commonName']);
    $args = [
        'clientid' => $reportRow['whmcs_client_id'],
        'paymentmethod' => 'paypalcheckout',
        'pid' => [$whmcsProduct->id],
        'domain' => [$domainName],
        'billingcycle' => [$billingcycle],
    ];
    $resultOfCreateOrder = localAPI(WHMCSApiActionType::AddOrder, $args);
    if ($resultOfCreateOrder['result'] != 'success') {
        $reportRow['status'] = ImportStatusType::NotImported;
        $reportRow['message'] = $resultOfCreateOrder['message'];

        return array_values($reportRow);
    }

    $resultOfAcceptOrderOrder = localAPI(
        WHMCSApiActionType::AcceptOrder,
        ['orderid' => $resultOfCreateOrder['orderid']]
    );
    if ($resultOfAcceptOrderOrder['result'] != 'success') {
        $reportRow['status'] = ImportStatusType::NotImported;
        $reportRow['message'] = $resultOfAcceptOrderOrder['message'];

        return array_values($reportRow);
    }

    return array_values($reportRow);
}
