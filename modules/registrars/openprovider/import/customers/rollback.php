<?php

require_once(__DIR__ . '/../../../../../init.php');
require_once(__DIR__ . '/../../openprovider.php');
require_once(__DIR__ . '/../functions.php');

use WHMCS\Database\Capsule;
use Carbon\Carbon;

use OpenProvider\WhmcsRegistrar\enums\FileOpenModeType;
use OpenProvider\WhmcsRegistrar\enums\ImportStatusType;
use OpenProvider\WhmcsRegistrar\enums\RollbackStatusType;
use OpenProvider\WhmcsRegistrar\enums\WHMCSApiActionType;
use OpenProvider\WhmcsRegistrar\enums\DatabaseTable;

use OpenProvider\WhmcsRegistrar\helpers\CSV;
use OpenProvider\WhmcsRegistrar\helpers\DB as DBHelper;
use OpenProvider\WhmcsRegistrar\helpers\Debug;

// Get csv file name from import report
$rollbackFilename = basename($argv[1]);
$importFileLinesCount = lines($rollbackFilename);

// Set required headers to check csv
$needleHeaders = [
    'fullname-company' => CSV::HeaderRequired,
    'customer_id'      => CSV::HeaderRequired,
    'status'           => CSV::HeaderRequired,
    'clientid'         => CSV::HeaderRequired,
    'errormessage'     => CSV::HeaderRequired,
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

$clients = $csv->getRecords();
$csv->close();

$counter = 1;
// Report header
$reportHeader = [
    $counter,
    'clientid',
    'fullname-company',
    'status',
    'errormessage',
];

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
$reportCsv = new CSV($reportFileName, FileOpenModeType::CreateAndWritePlus);
$reportCsv->setHeaders($reportHeader);
if (!$reportCsv->open()) {
    errorMessage('Can not create report file!');

    return;
}

// Write headers in file
$reportCsv->writeRecords([], CSV::WriteHeaders);

// Delete clients from WHMCS database
foreach ($clients as $client) {
    set_time_limit(3);
    $counter++;

    show_status($counter, $importFileLinesCount);

    $isDeleted    = false;
    $errorMessage = '';

    $isImportedByReport = $client['status'] == ImportStatusType::Imported
        && isset($client['clientid'])
        && !empty($client['clientid']);

    if ($isImportedByReport) {
        $canDeleteReasons = [
            'invoices' => true,
            'contacts' => true,
            'orders'   => true,
        ];

        // Check for invoices
        $invoices = localAPI(WHMCSApiActionType::GetInvoices, [
            'userid' => $client['clientid'],
        ]);
        if ($invoices['result'] == 'success' && $invoices['totalresults'] > 0) {
            $canDeleteReasons['invoices'] = false;

            $errorMessage .=
                "Client with clientid - {$client['clientid']} cant be removed."
                . "This client has invoices already!";
        }

        // If Invoices are not exists check contacts
        if ($canDeleteReasons['invoices']) {
            $contacts = localAPI(WHMCSApiActionType::GetContacts, [
                'userid' => $client['clientid'],
            ]);
            if ($contacts['result'] == 'success' && $contacts['totalresults'] > 0) {
                $canDeleteReasons['contacts'] = false;

                $errorMessage .=
                    "Client with clientid - {$client['clientid']} cant be removed."
                    . "This client has contacts already!";
            }
        }

        // If invoices and contacts not exists check orders
        if ($canDeleteReasons['contacts'] && $canDeleteReasons['invoices']) {
            $orders = localAPI(WHMCSApiActionType::GetOrders, [
                'userid' => $client['clientid'],
            ]);
            if ($orders['result'] == 'success' && $orders['totalresults'] > 0) {
                $canDeleteReasons['orders'] = false;

                $errorMessage .=
                    "Client with clientid - {$client['clientid']} cant be removed."
                    . "This client has orders already!";
            }
        }

        $canDeleteClient = $canDeleteReasons['invoices']
            && $canDeleteReasons['contacts']
            && $canDeleteReasons['orders'];

        if ($canDeleteClient) {
            try {
                // deleting client from Clients table
                $deleteClientWHMCS = Capsule::table('tblclients')
                    ->where('id', $client['clientid'])
                    ->delete();
                if (!$deleteClientWHMCS)
                    $errorMessage .= "Client not deleted in clients table.";

                // Get user connected with client
                $userClient = Capsule::table('tblusers_clients')
                    ->where('client_id', $client['clientid'])
                    ->first();

                $userClientId = $userClient->id;
                $userId       = $userClient->auth_user_id;

                // Delete user - client connection
                $deleteUserClientWHMCS = Capsule::table('tblusers_clients')
                    ->where('id', $userClientId)
                    ->delete();
                if (!$deleteUserClientWHMCS)
                    $errorMessage .= "Client not deleted in users_clients table.";

                // Delete user
                $deleteUserWHMCS = Capsule::table('tblusers')
                    ->where('id', $userId)
                    ->delete();
                if (!$deleteUserWHMCS)
                    $errorMessage .= "Client not deleted in users table.";

                $isDeleted = $deleteClientWHMCS && $deleteUserClientWHMCS && $deleteUserWHMCS
                    ? RollbackStatusType::Deleted
                    : RollbackStatusType::NotDeleted;

                // Delete mapping if contact deleted from clients table
                $canDeleteByAllParameters = $deleteClientWHMCS
                    && DBHelper::checkTableExist(DatabaseTable::MappingInternalExternalContacts);

                if ($canDeleteByAllParameters) {
                    $delete = Capsule::table(DatabaseTable::MappingInternalExternalContacts)
                        ->where('client_or_contact_id', $client['clientid'])
                        ->delete();
                }

            } catch (Exception $e) {
                $errorMessage .= $e->getMessage();
            }
        }
    } else {
        $errorMessage .= 'This user not imported by report.';
        $isDeleted    = RollbackStatusType::NotDeleted;
    }

    $reportItem = [
        $counter,
        'clientid'         => $client['clientid'],
        'fullname-company' => $client['fullname-company'],
        'deleted'          => $isDeleted,
        'errormessage'     => $errorMessage,
    ];

    $reportCsv->writeRecords([$reportItem], CSV::NoWriteHeaders);
}

$reportCsv->close();

function errorMessage($message, $args = []): void
{
    error_log(
        Debug::formatMessage(
            Debug::SEVERITY_ERROR,
            Debug::MODULE_IMPORT_CUSTOMERS,
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
            Debug::MODULE_IMPORT_CUSTOMERS,
            $message,
            $args
        )
    );
}