<?php

require_once(__DIR__ . '/../../../../../init.php');
require_once(__DIR__ . '/../../openprovider.php');

use WHMCS\Database\Capsule;
use Carbon\Carbon;

use OpenProvider\WhmcsRegistrar\enums\CSVHeaderType;
use OpenProvider\WhmcsRegistrar\enums\DatabaseTable;

use OpenProvider\WhmcsRegistrar\helpers\CSV;
use OpenProvider\WhmcsRegistrar\helpers\Debug;
use OpenProvider\WhmcsRegistrar\helpers\Report;
use OpenProvider\WhmcsRegistrar\helpers\DB as DBHelper;

const HEADERS = [
    'foreign_invoice_id' => CSVHeaderType::Required,
    'whmcs_invoice_id' => CSVHeaderType::Required,
    'foreign_customer_id' => CSVHeaderType::Required,
    'whmcs_client_id' => CSVHeaderType::Required,
    'imported' => CSVHeaderType::Required,
    'error' => CSVHeaderType::Required
];

try {
    echo Debug::SCRIPT_STARTED;
    $source = new CSV($argv[1]);

    if (!$source->open()) {
        throw new Exception("Couldn't open source file");
    }

    $source->setHeaders();
    if (!$source->checkHeaders(HEADERS)['success']) {
        throw new Exception("Required headers are missing from source: " . implode(', ', $source['missingHeaders']));
    }

    echo Debug::PREPARING_DATA;
    $invoices = $source->getRecords();
    $source->close();
    echo Debug::PREPARATION_DONE;

    $total = count($invoices);
    $c = 0;
    echo Debug::DELETING_OBJECTS;
    foreach ($invoices as $invoice) {
        $c++;
        $message = '';
        $ret = deleteInvoiceFromDb($invoice['whmcs_invoice_id']);
        foreach ($ret as $r) {
            if ($r['success']) {
                deleteFromImportMapping($invoice);
                continue;
            }
            $message .= " Table: {$r['table']} Column: {$r['column']} Message: {$r['message']}";
        }

        $report[] = [
            'foreign_invoice_id' => $invoice['foreign_invoice_id'],
            'whmcs_invoice_id' => $invoice['whmcs_invoice_id'],
            'foreign_customer_id' => $invoice['foreign_customer_id'],
            'whmcs_client_id' => $invoice['whmcs_client_id'],
            'deleted' => $message ? 0 : 1,
            'message' => $message ?: NULL
        ];
        echo Debug::progressBar($c, $total);
    }

    Debug::OPERATION_FINISHED;

    $dateTime = Carbon::now()->format('Y-m-d-H:i:s');
    $reportFIle = isset($argv[2]) ?: "rollback-{$dateTime}-from-{$argv[1]}-import.csv";;

    Report::save($reportFIle, $report);

    Debug::DONE;
    Debug::SCRIPT_FINISHED;
} catch (Throwable $t) {
    error_log(Debug::formatMessage(
        Debug::SEVERITY_ERROR,
        Debug::MODULE_IMPORT_INVOICES,
        "Rollback failed with error: {$t->getMessage()}"
    ));
    Debug::SCRIPT_FINISHED;

    return;
}

/**
 * @param int $id
 *
 * @return array
 */
function deleteInvoiceFromDb(int $id): array
{
    $data = [
        [
            'table' => DatabaseTable::Invoices,
            'column' => 'id'
        ],
        [
            'table' => DatabaseTable::InvoiceItems,
            'column' => 'invoiceid'
        ],
        [
            'table' => DatabaseTable::InvoiceData,
            'column' => 'invoice_id'
        ],
    ];
    $ret = [];

    foreach ($data as $arr) {
        if (!getOneFromDb($id, $arr['column'], $arr['table'])) {
            $ret[] = [
                'table' => $arr['table'],
                'column' => $arr['column'],
                'success' => 0,
                'message' => 'Record not found'
            ];

            continue;
        }
        Capsule::table($arr['table'])
            ->where($arr['column'], $id)
            ->delete();
        $ret[] = [
            'table' => $arr['table'],
            'column' => $arr['column'],
            'success' => 1,
            'message' => 'Record deleted'
        ];
    }

    return $ret;
}

/**
 * @param array $data
 *
 * @return array
 */
function deleteFromImportMapping(array $data): void
{
    Capsule::table(DatabaseTable::ImportedInvoicesMap)
        ->where('foreign_invoice_id', $data['foreign_invoice_id'])
        ->where('whmcs_invoice_id', $data['whmcs_invoice_id'])
        ->where('foreign_customer_id', $data['foreign_customer_id'])
        ->where('whmcs_client_id', $data['whmcs_client_id'])
        ->delete();

    return;
}

/**
 * @param string | int $value
 * @param string $column
 * @param string $table
 *
 * @return \Illuminate\Support\Collection
 */
function getOneFromDb(string $value, string $column, string $table)
{
    return Capsule::table($table)
        ->where($column, $value)
        ->first();
}
