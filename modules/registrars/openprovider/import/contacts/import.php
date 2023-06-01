<?php

require_once(__DIR__ . '/../../../../../init.php');
require_once(__DIR__ . '/../../openprovider.php');
require_once(__DIR__ . '/../functions.php');

use WHMCS\Database\Capsule;
use Carbon\Carbon;

use OpenProvider\WhmcsRegistrar\enums\DatabaseTable;
use OpenProvider\WhmcsRegistrar\enums\WHMCSApiActionType;
use OpenProvider\WhmcsRegistrar\enums\FileOpenModeType;
use OpenProvider\WhmcsRegistrar\enums\ImportStatusType;
use OpenProvider\WhmcsRegistrar\enums\ImportInternalContactType;
use OpenProvider\WhmcsRegistrar\enums\ImportSourceType;
use OpenProvider\WhmcsRegistrar\enums\ImportExternalContactType;

use OpenProvider\WhmcsRegistrar\helpers\CSV;
use OpenProvider\WhmcsRegistrar\helpers\DB as DBHelper;
use OpenProvider\WhmcsRegistrar\helpers\Debug;

// Get csv import file path
$importFilePath = $argv[1];
$importFileLinesCount = lines($importFilePath);

// Set required headers to check csv
$needleHeaders = [
    'firstname'      => CSV::HeaderOptional,
    'lastname'       => CSV::HeaderOptional,
    'email'          => CSV::HeaderOptional,
    'address1'       => CSV::HeaderOptional,
    'city'           => CSV::HeaderOptional,
    'state'          => CSV::HeaderOptional,
    'postcode'       => CSV::HeaderOptional,
    'country'        => CSV::HeaderOptional,
    'phonenumber'    => CSV::HeaderOptional,
    'customer_id'    => CSV::HeaderOptional,
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

// Checking header
$checkHeaders = $csv->checkHeaders($needleHeaders);
if (!$checkHeaders['success']) {
    $csv->close();

    errorMessage(
        'You have no following headers in your csv import file: '
        . implode(', ', $checkHeaders['missingHeaders']) . '.'
    );

    return;
}

// get records and close file stream
$contacts = $csv->getRecords();
$csv->close();

if (count($contacts) < 1) {
    errorMessage('Contacts list is empty');
    return;
}

if (!DBHelper::checkTableExist(DatabaseTable::MappingInternalExternalContacts)) {
    errorMessage(
        "Customer clients mapping table not exist! Before import contacts you need to import clients!"
    );

    return;
}

$counter      = 1;
$reportHeader = [
    $counter,
    'fullname-company',
    'client_id',
    'external_owner_id',
    'contact_id',
    'external_id',
    'status',
    'error_message',
];

// Generate report csv
$dateTime       = Carbon::now()->format('Y-m-d-H:i:s');
$reportFilePath = isset($argv[2]) ? $argv[2] : "./report-{$dateTime}.csv";

$reportCsv = new CSV($reportFilePath, FileOpenModeType::Write);
$reportCsv->setHeaders($reportHeader);

if (!$reportCsv->open()) {
    errorMessage('Can not create report file!');
    return;
}

$reportCsv->writeRecords([], CSV::WriteHeaders);

foreach ($contacts as &$contact) {
    set_time_limit(3);
    $counter++;

    show_status($counter, $importFileLinesCount);

    $reportItem = [
        $counter,
        'fullname-company'  => '',
        'client_id'         => '',
        'external_owner_id' => '',
        'contact_id'        => '',
        'external_id'       => '',
        'status'            => ImportStatusType::NotImported,
        'error_message'     => '',
    ];

    // Getting client id through customer id from database
    try {
        $customerClientId = Capsule::table(DatabaseTable::MappingInternalExternalContacts)
            ->where('external_id', $contact['customer_id'])
            ->where('internal_contact_type', ImportInternalContactType::Client)
            ->first();

    } catch (Exception $e) {
        $reportItem['error_message'] .= $e->getMessage();
        $reportItem['status']        = ImportStatusType::NotImported;
        $reportCsv->writeRecords([$reportItem], CSV::NoWriteHeaders);
        continue;
    }

    if (!$customerClientId) {
        $reportItem['error_message'] .=
            "Customer with customer_id {$contact['contact_id']} not exist in database!";

        $reportCsv->writeRecords([$reportItem], CSV::NoWriteHeaders);
        continue;
    }

    checkNeededFieldsAndModify($contact, $customerClientId->client_or_contact_id);

    $reportItem['client_id']         = $customerClientId->client_or_contact_id;
    $reportItem['external_owner_id'] = $customerClientId->external_id;
    $reportItem['external_id']       = $contact['contact_id'];

    // generate fullname-company from firstname, lastname and company for report
    $fullnameCompany = [];
    $fullname        = trim($contact['firstname'] . ' ' . $contact['lastname']);
    if ($fullname)
        $fullnameCompany[] = $fullname;

    if (isset($contact['company']) && !empty(trim($contact['company'])))
        $fullnameCompany[] = $contact['company'];

    if (count($fullnameCompany))
        $reportItem['fullname-company'] = implode('-', $fullnameCompany);


    // Create contact in whmcs
    $reply = localAPI(WHMCSApiActionType::AddContact, $contact);
    if ($reply['result'] == 'success') {
        $reportItem['contact_id'] = $reply['contactid'];
        $reportItem['status']     = ImportStatusType::Imported;

        // Add mapping internal contact with external contact
        try {
            $data = [
                'external_id'           => $contact['contact_id'],
                'client_or_contact_id'  => $reply['contactid'],
                'source_name'           => ImportSourceType::PowerPanel,
                'external_contact_type' => ImportExternalContactType::Contact,
                'internal_contact_type' => ImportInternalContactType::Contact,
            ];

            Capsule::table(DatabaseTable::MappingInternalExternalContacts)
                ->insert($data);

        } catch (Exception $e) {
            $reportItem['error_message'] .= "Contact mapping cant be save in database!";
        }

    } else {
        $reportItem['error_message'] .= 'Contact not saved in database! ' . $reply['message'];
        $reportItem['status']        = ImportStatusType::NotImported;
    }

    $reportCsv->writeRecords([$reportItem], CSV::NoWriteHeaders);
}

$reportCsv->close();

/**
 * Function check fields in contact array and
 * if its incorrect this function modify it.
 *
 * @param array $contact
 * @param int $clientId
 */
function checkNeededFieldsAndModify(array &$contact, int $clientId): void
{
    $contact['clientid'] = $clientId;

    if (!isset($contact['firstname']) && isset($contact['first_name']))
        $contact['firstname'] = $contact['first_name'];

    if (!isset($contact['lastname']) && isset($contact['last_name']))
        $contact['lastname'] = $contact['last_name'];

    if (!isset($contact['address1']) && isset($contact['street']))
        $contact['address1'] = $contact['street'];

    if (!isset($contact['company']) && isset($contact['company_name']))
        $contact['company'] = $contact['company_name'];

    if (!isset($contact['postcode']) && isset($contact['zip_code']))
        $contact['postcode'] = $contact['zip_code'];

    if (!isset($contact['country']) && isset($contact['country_code']))
        $contact['country'] = $contact['country_code'];

    if (isset($contact['country']))
        $contact['country'] = strtoupper($contact['country']);

    if (!isset($contact['phonenumber']) && isset($contact['phone_number']))
        $contact['phonenumber'] = $contact['phone_number'];
}

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