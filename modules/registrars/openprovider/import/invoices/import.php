<?php

require_once(__DIR__ . '/../../../../../init.php');
require_once(__DIR__ . '/../../openprovider.php');

use Carbon\Carbon;
use WHMCS\Database\Capsule;

use OpenProvider\WhmcsRegistrar\enums\DatabaseTable;
use OpenProvider\WhmcsRegistrar\helpers\CSV;
use OpenProvider\WhmcsRegistrar\helpers\Debug;
use OpenProvider\WhmcsRegistrar\helpers\Report;
use OpenProvider\WhmcsRegistrar\enums\WHMCSApiActionType;
use OpenProvider\WhmcsRegistrar\helpers\DB as DBHelper;

const INVOICE_HEADERS = [
    'status' => CSV::HeaderRequired,
    'draft' => CSV::HeaderOptional,
    'sendinvoice' => CSV::HeaderOptional,
    'paymentmethod' => CSV::HeaderOptional,
    'vat_percentage' => CSV::HeaderOptional,
    'creation_date' => CSV::HeaderRequired,
    'duedate' => CSV::HeaderOptional,
];

const LINE_HEADERS = [
    'description' => CSV::HeaderRequired,
    'price' => CSV::HeaderRequired,
    'vat' => CSV::HeaderOptional,
];

const STATUS_MAP = [
    'strOpen' => 'Unpaid', // Open
    'strVoldaan' => 'Paid', // Satisfied
    'strBijIncasso' => 'unpaid', // Sent to collection agency
    'strVerwijderd' => 'Cancelled', // Deleted
    'strOverDue' => 'Unpaid', // Overdue
    'strHerrinnerd' => 'Unpaid', // Reminder sent
    'strSommatieMail' => 'Unpaid', // Final reminder
    'strNabellen' => 'Unpaid', // Call customer
    'strImport' => 'Unpaid', // Imported
    'strReminderByPost' => 'Unpaid', // Reminder sent by mail
    'strControle' => 'Unpaid', // Waiting for approval
];

try {
    echo Debug::SCRIPT_STARTED;

    $dateTime = Carbon::now()->format('Y-m-d-H:i:s');
    $invoicesFile = new CSV($argv[1]);
    $linesFile = new CSV($argv[2]);
    $mode = $argv[3] ?: 'dry-run';
    $preparedData = $argv[4] ?: false;

    $customers = Capsule::table(DatabaseTable::MappingInternalExternalContacts)
        ->where('source_name', 'powerpanel')
        ->where('external_contact_type', 'customer')
        ->where('internal_contact_type', 'client')
        ->get();

    if (count($customers) === 0) {
        throw new Exception("No imported PowerPanel customers found. Please run customers import first");
    }
    if (!$invoicesFile->open() || !$linesFile->open()) {
        throw new Exception("Couldn't open source file");
    }

    $invoicesFile->setHeaders();
    $linesFile->setHeaders();

    $headersValidation = [
        $invoicesFile->getFilepath() => $invoicesFile->checkHeaders(INVOICE_HEADERS),
        $linesFile->getFilepath() => $linesFile->checkHeaders(LINE_HEADERS)
    ];

    foreach ($headersValidation as $f => $r) {
        if (!$r['success']) {
            throw new Exception("Required headers are missing from $f: " . implode(', ', $r['missingHeaders']));
        }
    }

    echo Debug::PREPARING_DATA;
    if ($preparedData) {
        $invoices = json_decode(file_get_contents($preparedData), true);
    } else {
        $invoices = formatData($customers, $invoicesFile->getRecords(), $linesFile->getRecords());
        file_put_contents("./formattedDataCache-{$dateTime}.json", json_encode($invoices));
    }
    echo Debug::PREPARATION_DONE;

    switch ($mode) {
        case 'import-one':
            $limit = 1;
            break;
        case 'import-all':
            $limit = count($invoices);
            break;
        case 'dry-run':
        default:
            $limit = false;
    }

    $total = count($invoices);
    $c = 0;
    echo Debug::IMPORTING_OBJECTS;
    foreach ($invoices as $id => $invoice) {
        $c++;
        $data = [];
        $skip = 0;
        if ($limit && $c > $limit) {
            echo Debug::LIMIT_REACHED;
            echo "Import limit reached";
            break;
        }
        if (getOneFromDb($id, 'foreign_invoice_id', DatabaseTable::ImportedInvoicesMap)) {
            $skip = 1;
            $ret['message'] = 'Invoice was already imported';
        }
        if ($limit && !$skip) {
            $ret = localAPI(WHMCSApiActionType::CreateInvoice, $invoice);
        }
        $data = [
            'foreign_invoice_id' => $id,
            'whmcs_invoice_id' => $ret['invoiceid'] ?: 0,
            'foreign_customer_id' => getPpCustomerId($invoice['userid']),
            'whmcs_client_id' => $invoice['userid'],
            'imported' => $ret['result'] === 'success' ? 1 : 0,
            'error' => $ret['message'] ?: NULL
        ];
        $report[] = $data;
        if ($ret['result'] === 'success') {
            mappingInvoicesClient(array_splice($data, 0, 4));
        }
        echo Debug::progressBar($c, $total);
    }
    echo Debug::OPERATION_FINISHED;

    $reportFile = isset($argv[5]) ?: "./report-{$dateTime}.csv";

    Report::save($reportFile, $report);

    echo Debug::DONE;
    echo Debug::SCRIPT_FINISHED;
} catch (Throwable $t) {
    error_log(Debug::formatMessage(
        Debug::SEVERITY_ERROR,
        Debug::MODULE_IMPORT_INVOICES,
        "Import failed with error: {$t->getMessage()}"
    ));
    echo Debug::SCRIPT_FINISHED;

    return;
}

/**
 * @param \Illuminate\Support\Collection $customers
 * @param array $iSource
 * @param array $lSource
 *
 * @return array
 */
function formatData(Illuminate\Support\Collection $customers, array $iSource, array $lSource): array
{
    if (count($iSource) === 0 || count($lSource) === 0) {
        throw new Exception("No invoices or lines data to import. Aborting");
    }

    $invoices = [];
    $total = count($customers);
    $done = 0;
    foreach ($customers as $customer) {
        foreach ($iSource as $i) {
            if ($customer->external_id != $i['customer_id']) {
                continue;
            }

            $lines = [];
            foreach ($lSource as $l) {
                if ($i['invoice_id'] === $l['invoice_id']) {
                    $lines[] = $l;
                }
            }
            $invoices[$i['invoice_id']] = map($i, $customer->client_or_contact_id, $lines);
        }
        $done++;
        echo Debug::progressBar($done, $total);
    }

    return $invoices;
}

/**
 * @param array $i
 * @param int $clientId
 * @param array $lines
 *
 * @return array
 */
function map(array $i, int $clientId, array $lines): array
{
    $invoice = [
        'userid' => $clientId,
        'status' => STATUS_MAP[$i['status']],
        'draft' => $i['draft'] ?: false,
        'sendinvoice' => $i['sendinvoice'] ?: false,
        'paymentmethod' => $i['paymentmethod'] ?: 'mailin',
        'taxrate' => $i['vat_percentage'] ?: NULL,
        'creation_date' => (new DateTime($i['creation_date']))->format('Y-m-d'),
        'duedate' => (new DateTime($i['duedate']))->format('Y-m-d'),
        'notes' => "Imported from Powerpanel. PP invoice ID: {$i['invoice_id']} PP status: {$i['status']}"
    ];

    if (count($lines) > 0) {
        $c = 0;
        foreach ($lines as $line) {
            $c++;
            $invoice['itemdescription' . $c] = $line['description'];
            $invoice['itemamount' . $c] = $line['price'];
            $invoice['itemtaxed' . $c] = $line['vat'] ? 1 : 0;
        }
    }

    return $invoice;
}

/**
 * @param int $userId
 *
 * @return Illuminate\Support\Collection
 */
function getPpCustomerId(int $userId)
{
    return Capsule::table(DatabaseTable::MappingInternalExternalContacts)
        ->where('id', $userId)
        ->first()
        ->external_id;
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
    if (DBHelper::checkTableExist($table)) {
        return Capsule::table($table)
            ->where($column, $value)
            ->first();
    }
}

/**
 * @param array $ImportedInvoicesMap
 */
function mappingInvoicesClient(array $data): void
{
    if (!DBHelper::checkTableExist(DatabaseTable::ImportedInvoicesMap)) {
        try {
            Capsule::schema()
                ->create(
                    DatabaseTable::ImportedInvoicesMap,
                    function ($table) {
                        /** @var \Illuminate\Database\Schema\Blueprint $table */
                        $table->increments('id');
                        $table->unsignedInteger('foreign_invoice_id');
                        $table->unsignedInteger('whmcs_invoice_id');
                        $table->unsignedInteger('foreign_customer_id');
                        $table->unsignedInteger('whmcs_client_id');
                        $table->timestamps();
                    }
                );
        } catch (Exception $e) {
            error_log(
                Debug::formatMessage(
                    Debug::SEVERITY_ERROR,
                    Debug::MODULE_IMPORT_INVOICES,
                    "Table " . DatabaseTable::ImportedInvoicesMap . " can't be created!"
                )
            );
            error_log(
                Debug::formatMessage(
                    Debug::SEVERITY_ERROR,
                    Debug::MODULE_IMPORT_INVOICES,
                    $e->getMessage(),
                )
            );
            return;
        }
    }

    try {
        Capsule::table(DatabaseTable::ImportedInvoicesMap)
            ->insert($data);
    } catch (Exception $e) {
        error_log(
            Debug::formatMessage(
                Debug::SEVERITY_ERROR,
                Debug::MODULE_IMPORT_INVOICES,
                "Data cant be store in database!",
            )
        );
        return;
    }
}
