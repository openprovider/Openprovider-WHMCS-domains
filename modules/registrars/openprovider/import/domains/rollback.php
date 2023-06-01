<?php

require_once(__DIR__ . '/../../../../../init.php');
require_once(__DIR__ . '/../../openprovider.php');
require_once(__DIR__ . '/../functions.php');

use WHMCS\Database\Capsule;
use Carbon\Carbon;

use OpenProvider\WhmcsRegistrar\enums\FileOpenModeType;
use OpenProvider\WhmcsRegistrar\enums\ImportStatusType;
use OpenProvider\WhmcsRegistrar\enums\RollbackStatusType;
use OpenProvider\WhmcsRegistrar\enums\DatabaseTable;

use OpenProvider\WhmcsRegistrar\helpers\CSV;
use OpenProvider\WhmcsRegistrar\helpers\Debug;

// Get csv file name from import report
$rollbackFilename = basename($argv[1]);

// Set required headers to check csv
$needleHeaders = [
    'domain'              => CSV::HeaderRequired,
    'domain_id'           => CSV::HeaderRequired,
    'external_contact_id' => CSV::HeaderRequired,
    'client_id'           => CSV::HeaderRequired,
    'contact_id'          => CSV::HeaderRequired,
    'status'              => CSV::HeaderRequired,
    'error_message'       => CSV::HeaderRequired,
];

// Parsing csv report file
$csv = new CSV($rollbackFilename);
if (!$csv->open()) {
    errorMessage("File {$rollbackFilename} open Failed!");
    return;
}

$csv->setHeaders();

$checkHeaders = $csv->checkHeaders($needleHeaders);
if (!$checkHeaders['success']) {
    $csv->close();

    infoMessage(
        'You have no following headers in your csv import file: '
        . implode(', ', $checkHeaders['missingHeaders']) . '.'
    );

    return;
}

$domains = $csv->getRecords();
$csv->close();

removeAddedDomainsFromWhmcs($domains, $rollbackFilename);

function removeAddedDomainsFromWhmcs($domains, $rollbackFilename): void
{
    $counter = 1;
    // Report header
    $reportHeader = [
        $counter,
        'domain',
        'domain_id',
        'external_contact_id',
        'client_id',
        'contact_id',
        'status',
        'error_message',
    ];

    $reportLinesCount = lines($rollbackFilename);

    try {
        $importDate = substr($rollbackFilename, strpos($rollbackFilename, 'report-') + 7, 19);
        $date       = Carbon::createFromFormat('Y-m-d-H:i:s', $importDate);
        if ($date && $date->isValid())
            $reportFileName = "rollback-from-{$importDate}-import.csv";
        else
            $reportFileName = "rollback-from-\"{$rollbackFilename}\"-import.csv";
    } catch (Exception $e) {
        $reportFileName = "rollback-from-\"{$rollbackFilename}\"-import.csv";
    }

    // Generate report file
    $reportCsv = new CSV($reportFileName, FileOpenModeType::Write);
    $reportCsv->setHeaders($reportHeader);
    if (!$reportCsv->open()) {
        errorMessage("Cant create report file!");
        return;
    }

    // Write headers in report
    $reportCsv->writeRecords([], CSV::WriteHeaders);

    foreach ($domains as $domain) {

        set_time_limit(3);

        $counter++;

        // show status bar in console
        show_status($counter, $reportLinesCount);

        $reportItem = [
            $counter,
            'domain' => $domain['domain'],
            'domain_id' => $domain['domain_id'],
            'eternal_contact_id' => $domain['external_contact_id'],
            'client_id' => $domain['client_id'],
            'contact_id' => $domain['contact_id'],
            'status' => RollbackStatusType::NotDeleted,
            'error_message' => '',
        ];


        $domainWasImported = $domain['status'] == ImportStatusType::NotImported
            || $domain['status'] == ImportStatusType::Skipped;
        if ($domainWasImported) {
            $reportItem['error_message'] = "Domain not imported by this report.";
            $reportCsv->writeRecords([$reportItem], CSV::NoWriteHeaders);
            continue;
        }

        $orderId = getOrderIdByDomainId($domain['domain_id']);
        if (!$orderId) {
            $reportItem['error_message'] .= "Cant find order id for this domain.";
        }

        // Delete domain from domains table
        $deleteDomain = false;
        try {
            $deleteDomain = Capsule::table(DatabaseTable::Domains)
                ->where('id', $domain['domain_id'])
                ->delete();

            if (!$deleteDomain)
                $reportItem['error_message'] .= 'Domain cant be deleted from database!';

        } catch (Exception $e) {
            errorMessage(
                'Cant delete domain record from database',
                ['message' => $e->getMessage(), 'code' => $e->getCode()]
            );

            $reportItem['error_message'] .= 'Cant delete domain from database!';
        }

        // Delete domain's order from orders table
        $deleteOrder = false;
        try {
            $deleteOrder = Capsule::table(DatabaseTable::Orders)
                ->where('id', $orderId)
                ->delete();

            if (!$deleteOrder) {
                $reportItem['error_message'] .= 'Cant delete order from database!';
            }
        } catch (Exception $e) {
            $reportItem['error_message'] .= 'Cant delete order from database!';

            errorMessage(
                'Cant delete order from database!',
                ['message' => $e->getMessage(), 'code' => $e->getCode()]
            );
        }

        $reportItem['status'] = $deleteOrder && $deleteDomain
            ? RollbackStatusType::Deleted
            : RollbackStatusType::NotDeleted;

        $reportCsv->writeRecords([$reportItem], CSV::NoWriteHeaders);
    }
}

function getOrderIdByDomainId($domainId): int
{
    try {
        $domain = Capsule::table(DatabaseTable::Domains)
            ->where('id', $domainId)
            ->select('orderid')
            ->first();

        if ($domain->orderid)
            return $domain->orderid;
        return 0;
    } catch (Exception $e) {
        errorMessage(
            'Cant get domain by id from database',
            ['message' => $e->getMessage(), 'code' => $e->getCode()]
        );
        return 0;
    }
}

function errorMessage($message, $args = []): void
{
    error_log(
        Debug::formatMessage(
            Debug::SEVERITY_ERROR,
            Debug::MODULE_IMPORT_DOMAINS,
            $message,
            $args
        )
    );
}

function infoMessage($message, $args = []): void
{
    error_log(
        Debug::formatMessage(
            Debug::SEVERITY_INFO,
            Debug::MODULE_IMPORT_DOMAINS,
            $message,
            $args
        )
    );
}