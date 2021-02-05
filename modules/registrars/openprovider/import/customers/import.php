<?php

require_once(__DIR__ . '/../../../../../init.php');
require_once(__DIR__ . '/../../openprovider.php');
require_once(__DIR__ . '/../functions.php');

use Carbon\Carbon;
use WHMCS\Database\Capsule;

use OpenProvider\WhmcsRegistrar\enums\FileOpenModeType;
use OpenProvider\WhmcsRegistrar\enums\ImportStatusType;
use OpenProvider\WhmcsRegistrar\enums\ImportSourceType;
use OpenProvider\WhmcsRegistrar\enums\ImportInternalContactType;
use OpenProvider\WhmcsRegistrar\enums\ImportExternalContactType;
use OpenProvider\WhmcsRegistrar\enums\WHMCSApiActionType;
use OpenProvider\WhmcsRegistrar\enums\DatabaseTable;

use OpenProvider\WhmcsRegistrar\helpers\CSV;
use OpenProvider\WhmcsRegistrar\helpers\DB as DBHelper;
use OpenProvider\WhmcsRegistrar\helpers\Debug;

// Get csv import file path
$importFilePath = $argv[1];

$importFileLinesCount = lines($importFilePath);

// Set required headers to check csv
$needleHeaders = [
    'firstname'      => CSV::HeaderRequired,
    'lastname'       => CSV::HeaderRequired,
    'email'          => CSV::HeaderRequired,
    'street'         => CSV::HeaderRequired,
    'number'         => CSV::HeaderRequired,
    'city'           => CSV::HeaderRequired,
    'state'          => CSV::HeaderRequired,
    'postcode'       => CSV::HeaderRequired,
    'country'        => CSV::HeaderRequired,
    'phonenumber'    => CSV::HeaderRequired,
    'customer_id'    => CSV::HeaderRequired,
    'companyname'    => CSV::HeaderOptional,
    'address2'       => CSV::HeaderOptional,
    'tax_id'         => CSV::HeaderOptional,
    'password2'      => CSV::HeaderOptional,
    'securityqid'    => CSV::HeaderOptional,
    'securityqans'   => CSV::HeaderOptional,
    'currency'       => CSV::HeaderOptional,
    'groupid'        => CSV::HeaderOptional,
    'customerfields' => CSV::HeaderOptional,
    'language'       => CSV::HeaderOptional,
    'notes'          => CSV::HeaderOptional,
    'marketingoptin' => CSV::HeaderOptional,
    'noemail'        => CSV::HeaderOptional,
    'resetpassword'  => CSV::HeaderOptional,
];

// Parsing csv file
$csv = new CSV($importFilePath);

if (!$csv->open()) {
    errorMessage("File {$importFilePath} open Failed!");
    return;
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

$clients = $csv->getRecords();
$csv->close();

if (count($clients) < 1) {
    infoMessage("The csv file is empty.");
    return;
}

$dateTime       = Carbon::now()->format('Y-m-d-H:i:s');
$reportFilePath = isset($argv[2]) ? $argv[2] : "./report-{$dateTime}.csv";

$counter = 1;

// Set report headers
$headers = [
    $counter,
    'fullname-company',
    'customer_id',
    'status',
    'clientid',
    'errormessage',
];

$reportCsv = new CSV($reportFilePath, FileOpenModeType::CreateAndWritePlus);
$reportCsv->setHeaders($headers);

if (!$reportCsv->open()) {
    errorMessage('Can not create report file!');
}

// Write headers in file
$reportCsv->writeRecords([], CSV::WriteHeaders);

// add clients to WHMCS
foreach ($clients as &$client) {
    set_time_limit(3);

    $counter++;

    show_status($counter, $importFileLinesCount);

    checkNeededFieldsAndModify($client);

    $result = localAPI(WHMCSApiActionType::AddClient, $client);

    $clientId                 = $result['clientid'];
    $customerIdFromPowerPanel = $client['customer_id'];
    $errorMessage             = $result['message'];

    $clientFullname           = trim("{$client['lastname']} {$client['firstname']}");
    $clientFullnameAndCompany = isset($client['companyname']) && !empty($client['companyname'])
        ? trim("{$clientFullname}-{$client['companyname']}")
        : $clientFullname;

    if ($result['result'] == 'success') {
        $status         = ImportStatusType::Imported;
        $customerClient = [
            'external_id'           => $customerIdFromPowerPanel,
            'client_or_contact_id'  => $clientId,
            'source_name'           => ImportSourceType::PowerPanel,
            'internal_contact_type' => ImportInternalContactType::Client,
            'external_contact_type' => ImportExternalContactType::Customer,
        ];

        mappingCustomerClient($customerClient);
    } else
        $status = ImportStatusType::NotImported;

    $reportItem = [
        $counter,
        $clientFullnameAndCompany,
        $customerIdFromPowerPanel,
        $status,
        $clientId,
        $errorMessage
    ];
    $reportCsv->writeRecords([$reportItem], CSV::NoWriteHeaders);
}
$reportCsv->close();


/**
 * Function to generate random strong password.
 *
 * @return string
 */
function generateRandomPassword(): string
{
    $alphabet    = "abcdefghijklmnopqrstuwxyzABCDEFGHIJKLMNOPQRSTUWXYZ0123456789";
    $passwordLen = 10;
    $pass        = '';
    for ($i = 0; $i < $passwordLen; $i++) {
        $n    = rand(0, strlen($alphabet) - 1);
        $pass .= $alphabet[$n];
    }
    return $pass;
}

/**
 * Function to check input data and if it wrong format modify it.
 *
 * @param $client
 */
function checkNeededFieldsAndModify(&$client): void
{
    if (!isset($client['password2']) || empty($client['password2'])) {
        $client['password2']     = generateRandomPassword();
        $client['resetpassword'] = true;
    }

    $client['country'] = strtoupper($client['country']);

    // Generate address from street and number.
    $client['address1'] = $client['street'];

    if (!isset($client['state']) || empty($client['state'])) {
        $client['state'] = 'Undefined';
    }

    if (isset($client['phonenumber']) && !empty($client['phonenumber'])) {
        $client['phonenumber'] = explode('; ', $client['phonenumber'])[0];
    }
}

/**
 * @param array $customerClientId
 */
function mappingCustomerClient(array $customerClientId): void
{
    if (!DBHelper::checkTableExist(DatabaseTable::MappingInternalExternalContacts)) {
        try {
            Capsule::schema()
                ->create(
                    DatabaseTable::MappingInternalExternalContacts,
                    function ($table) {
                        /** @var \Illuminate\Database\Schema\Blueprint $table */
                        $table->increments('id');
                        $table->unsignedInteger('external_id');
                        $table->unsignedInteger('client_or_contact_id');
                        $table->string('source_name');
                        $table->string('external_contact_type')->nullable();
                        $table->string('internal_contact_type');
                        $table->timestamps();
                    }
                );
        } catch (Exception $e) {
            errorMessage(
                "Table " . DatabaseTable::MappingInternalExternalContacts . " can't be created!"
            );
            errorMessage(
                $e->getMessage()
            );
            return;
        }
    }

    try {
        Capsule::table(DatabaseTable::MappingInternalExternalContacts)
            ->insert($customerClientId);
    } catch (Exception $e) {
        errorMessage(
            "Data cant be store in database!"
        );
        return;
    }
}

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
