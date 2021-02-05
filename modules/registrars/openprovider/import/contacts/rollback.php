<?php

require_once(__DIR__ . '/../../../../../init.php');
require_once(__DIR__ . '/../../openprovider.php');
require_once(__DIR__ . '/../functions.php');

use WHMCS\Database\Capsule;
use Carbon\Carbon;

use OpenProvider\WhmcsRegistrar\enums\FileOpenModeType;
use OpenProvider\WhmcsRegistrar\enums\ImportStatusType;
use OpenProvider\WhmcsRegistrar\enums\ImportInternalContactType;
use OpenProvider\WhmcsRegistrar\enums\RollbackStatusType;
use OpenProvider\WhmcsRegistrar\enums\WHMCSApiActionType;
use OpenProvider\WhmcsRegistrar\enums\DatabaseTable;

use OpenProvider\WhmcsRegistrar\helpers\CSV;
use OpenProvider\WhmcsRegistrar\helpers\Debug;

// Get csv file name from import report
$rollbackFilename = basename($argv[1]);
$importFileLinesCount = lines($rollbackFilename);

// Set required headers to check csv
$needleHeaders = [
    'fullname-company' => CSV::HeaderRequired,
    'external_id'      => CSV::HeaderRequired,
    'client_id'        => CSV::HeaderRequired,
    'contact_id'       => CSV::HeaderRequired,
    'status'           => CSV::HeaderRequired,
    'error_message'    => CSV::HeaderRequired,
];

// Parsing csv report file
$csv = new CSV($rollbackFilename);
if (!$csv->open()) {
    errorMessage("File {$rollbackFilename} open Failed!");
}

$csv->setHeaders();

$checkHeaders = $csv->checkHeaders($needleHeaders);
if (!$checkHeaders['success']) {
    $csv->close();

    errorMessage(
        'You have no following headers in your csv import file: '
        . implode(', ', $checkHeaders['missingHeaders']) . '.'
    );

    return;
}

$contacts = $csv->getRecords();
$csv->close();

$counter = 1;

$reportHeader = [
    $counter,
    'contact_id',
    'fullname-company',
    'status',
    'error_message',
];

// Generate rollback report name
try {
    $importDate = substr($rollbackFilename, strpos($rollbackFilename, 'report-') + 7, 19);
    $date = Carbon::createFromFormat('Y-m-d-H:i:s', $importDate);
    if ($date && $date->isValid())
        $reportFileName = "rollback-from-{$importDate}-import.csv";
    else
        $reportFileName = "rollback-from-\"{$rollbackFilename}\"-import.csv";
} catch (Exception $e) {
    $reportFileName = "rollback-from-\"{$rollbackFilename}\"-import.csv";
}

// Generate rollback report
$reportCsv = new CSV($reportFileName, FileOpenModeType::Write);
$reportCsv->setHeaders($reportHeader);
if (!$reportCsv->open()) {
    errorMessage('Can not create report file!');
}

$reportCsv->writeRecords([], CSV::WriteHeaders);

foreach ($contacts as $contact)
{
    set_time_limit(3);

    $counter++;

    show_status($counter, $importFileLinesCount);

    $reportItem = [
        $counter,
        'contact_id'       => $contact['contact_id'],
        'fullname-company' => $contact['fullname-company'],
        'status'           => RollbackStatusType::NotDeleted,
        'error_message'    => '',
    ];

    if ($contact['status'] == ImportStatusType::Imported) {
        $params = [
            'contactid' => $contact['contact_id'],
        ];

        $reply = localApi(WHMCSApiActionType::DeleteContact, $params);

        if ($reply['result'] == 'success') {
            $reportItem['status'] = RollbackStatusType::Deleted;

            // Delete mapping
            try {
                Capsule::table(DatabaseTable::MappingInternalExternalContacts)
                    ->where('client_or_contact_id', $contact['contact_id'])
                    ->where('external_id', $contact['external_id'])
                    ->where('internal_contact_type', ImportInternalContactType::Contact)
                    ->delete();

            } catch (Exception $e) {
                $reportItem['error_message'] .= $e->getMessage();
            }

        } else {
            $reportItem['error_message'] = $reply['message'];
        }

    } else {
        $reportItem['error_message'] = "Contact not imported!";
    }

    $reportCsv->writeRecords([$reportItem], CSV::NoWriteHeaders);
}

$reportCsv->close();

function errorMessage($message, $args = []): void
{
    error_log(
        Debug::formatMessage(
            Debug::SEVERITY_ERROR,
            Debug::MODULE_IMPORT_CONTACTS,
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
            Debug::MODULE_IMPORT_CONTACTS,
            $message,
            $args
        )
    );
}
