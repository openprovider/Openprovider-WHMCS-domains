<?php

require_once(__DIR__ . '/../../../../../init.php');
require_once(__DIR__ . '/../../openprovider.php');
require_once(__DIR__ . '/../functions.php');

use Carbon\Carbon;
use WHMCS\Database\Capsule;

use OpenProvider\WhmcsRegistrar\enums\FileOpenModeType;
use OpenProvider\WhmcsRegistrar\enums\DatabaseTable;

use OpenProvider\WhmcsRegistrar\enums\ImportStatusType;
use OpenProvider\WhmcsRegistrar\enums\ImportExternalContactType;
use OpenProvider\WhmcsRegistrar\enums\ImportInternalContactType;
use OpenProvider\WhmcsRegistrar\enums\WHMCSApiActionType;

use OpenProvider\WhmcsRegistrar\helpers\CSV;
use OpenProvider\WhmcsRegistrar\helpers\Debug;

// Import error messages
const ERROR_FILE_OPEN_FAILED                 = 'File open failed!';
const ERROR_CSV_FILE_EMPTY                   = 'Domains are empty in csv!';
const ERROR_CANT_CREATE_REPORT_FILE          = 'Cant open or create report file.';
const ERROR_DOMAIN_STATUS_NOT_VALID          = 'Domain status not valid to import!';
const ERROR_DOMAIN_HAS_NO_CLIENT             = 'This domain has no client!';
const ERROR_DOMAIN_ALREADY_EXIST_IN_DATABASE = 'This domain already exist in database!';
const ERROR_CANT_GET_ACCESS_TO_DATABASE      = 'Cant get access to database to check that domain not exist.';
const ERROR_CLIENT_HAS_NO_USER               = 'Client has no user!';
const ERROR_NO_USER_BY_CLIENT_ID             = 'Cant find user id by client id';
const ERROR_CANT_CREATE_ORDER_RECORD         = 'Cant create order record';
const ERROR_CANT_GET_LAST_ORDER_RECORD       = 'Cant get last order record';
const ERROR_CANT_GET_LAST_DOMAIN_RECORD      = 'Cant get last domain record';
const ERROR_CANT_ACCEPT_ORDER                = 'Cant accept order.';
const ERROR_CANT_CREATE_DOMAIN               = 'Cant create domain.';
const ERROR_ADDED_TO_DEFAULT_CLIENT          = 'This domain has no client in database, system set default client.';
const ERROR_DOMAIN_PREMIUM                   = 'This domain is premium. Premium domains not allowed by module.';
const ERROR_INVALID_SUBSCRIPTION_PERIOD      = 'This domain has small subscription period.';
const ERROR_TLD_NOT_EXIST_IN_DOMAIN_PRICING  = 'The domain has tld that not exist in tld pricing in whmcs.';

const WARNING_SUBSCRIPTION_PERIOD_SMALLER_THEN_YEAR = 'This domain has subscription period smaller then year. The system set period of one year as default.';

$importConfig = require (__DIR__ . '/../domain-import-config.php');

// Set default values
$defaultClientId  = (isset($importConfig['DEFAULT_CLIENT_ID'])
    && !empty($importConfig['DEFAULT_CLIENT_ID']))
        ? $importConfig['DEFAULT_CLIENT_ID']
        : false;

$defaultContactId = (isset($importConfig['DEFAULT_CONTACT_ID'])
    && !empty($importConfig['DEFAULT_CONTACT_ID']))
        ? $importConfig['DEFAULT_CONTACT_ID']
        : false;

$domainStatusesToImport = (isset($importConfig['DOMAIN_STATUSES_TO_IMPORT'])
    && count($importConfig['DOMAIN_STATUSES_TO_IMPORT']) > 0)
        ? $importConfig['DOMAIN_STATUSES_TO_IMPORT']
        : ['DOMAIN_STATUS_ACTIVE'];

$paymentMethod = (isset($importConfig['DEFAULT_PAYMENT_METHOD'])
    && !empty($importConfig['DEFAULT_PAYMENT_METHOD']))
        ? $importConfig['DEFAULT_PAYMENT_METHOD']
        : 'mailin';

$nextDueDateOffsetFromExpiryDate = (isset($importConfig['NEXT_DUE_DATE_OFFSET_FROM_EXPIRY_DATE'])
    && !empty($importConfig['NEXT_DUE_DATE_OFFSET_FROM_EXPIRY_DATE']))
    ? $importConfig['NEXT_DUE_DATE_OFFSET_FROM_EXPIRY_DATE']
    : 0;

$currency = (isset($importConfig['CURRENCY_CODE'])
    && !empty($importConfig['CURRENCY_CODE']))
    ? $importConfig['CURRENCY_CODE']
    : false;

// Get import file
$importFile = $argv[1];

$importFileLinesCount = lines($importFile);

$csv = new CSV($importFile);

if (!$csv->open()) {
    errorMessage(ERROR_FILE_OPEN_FAILED);
    return;
}

$csv->setHeaders();
$domains = $csv->getRecords();
$csv->close();

if (count($domains) < 1) {
    infoMessage(ERROR_CSV_FILE_EMPTY);
    return;
}

$counter = 1;
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

$dateTime = Carbon::now()->format('Y-m-d-H:i:s');
$reportFilePath = isset($argv[2]) ? $argv[2] : "./report-{$dateTime}.csv";
$reportCsv = new CSV($reportFilePath, FileOpenModeType::Write);

if (!$reportCsv->open()) {
    errorMessage(ERROR_CANT_CREATE_REPORT_FILE);
    return;
}

$reportCsv->setHeaders($reportHeader);
$reportCsv->writeRecords([], CSV::WriteHeaders);

// Get currency id
$currencyId = '';
if (!$currency) {
    try {
        $currencyRecord = Capsule::table(DatabaseTable::Currencies)
            ->where('default', 1)
            ->select('id', 'code')
            ->first();
        if (!$currencyRecord) {
            errorMessage('You have no currencies in whmcs');
            return;
        }

        $currencyId = $currencyRecord->id;
    } catch (Exception $e) {
        errorMessage(
            'Script cant get currency id from database',
            ['message' => $e->getMessage(), 'code' => $e->getCode()]
        );
    }
} else {
    try {
        $currencyRecord = Capsule::table(DatabaseTable::Currencies)
            ->where('code', $currency)
            ->first();

        if (!$currencyRecord) {
            errorMessage('You have no currency with code ' . $currency);
            return;
        }

        $currencyId = $currencyRecord->id;
    } catch (Exception $e) {
        errorMessage(
            'Script cant get currency id from database',
            ['message' => $e->getMessage(), 'code' => $e->getCode()]
        );
    }

}

// Get tld pricing by currency id
$tldPricing = localApi(
    WHMCSApiActionType::GetTLDPricing,
    [
        'currencyid' => $currencyId,
    ]
);

// Lve only tld prices
$tldPrices = $tldPricing['pricing'];

foreach ($domains as &$domain) {
    set_time_limit(3);

    $counter++;

    // show status bar
    show_status($counter, $importFileLinesCount);

    $reportItem = [
        $counter,
        'domain'              => $domain['domain_name_ascii'],
        'domain_id'           => '',
        'external_contact_id' => $domain['customer_id'],
        'client_id'           => '',
        'contact_id'          => '',
        'status'              => ImportStatusType::NotImported,
        'error_message'       => '',
    ];

    // If subscription period monthly or quarterly skip domain import
    if (!empty($domain['subscription_period']) && intval($domain['subscription_period']) < 4) {
        $reportItem['error_message'] = ERROR_INVALID_SUBSCRIPTION_PERIOD;
        $reportItem['status'] = ImportStatusType::Skipped;
        $reportCsv->writeRecords([$reportItem], CSV::NoWriteHeaders);
        continue;
    }

    // If Domain is premium skip domain import
    if (!!intval($domain['is_premium'])) {
        $reportItem['error_message'] = ERROR_DOMAIN_PREMIUM;
        $reportItem['status'] = ImportStatusType::Skipped;
        $reportCsv->writeRecords([$reportItem], CSV::NoWriteHeaders);
        continue;
    }

    // Check domain status. If domain status incorrect skip domain import
    if (!checkDomainStatusValidity($domain['domain_status'], $domainStatusesToImport)) {
        $reportItem['error_message'] = ERROR_DOMAIN_STATUS_NOT_VALID;
        $reportItem['status'] = ImportStatusType::Skipped;
        $reportCsv->writeRecords([$reportItem], CSV::NoWriteHeaders);
        continue;
    }

    // Get Customer and client ids. if ids not exist,
    // then set default ids from config file
    // If client id is empty skip domain import
    $clientId = getClientIdByCustomerId($domain['customer_id']);
    if (!$clientId) {
        if (!$defaultClientId) {
            $reportItem['error_message'] = ERROR_DOMAIN_HAS_NO_CLIENT;
            $reportCsv->writeRecords([$reportItem], CSV::NoWriteHeaders);
            continue;
        }
        $clientId = $defaultClientId;
        $reportItem['error_message'] = ERROR_ADDED_TO_DEFAULT_CLIENT;
    }

    $reportItem['client_id'] = $clientId;

    $contactId = getContactIdByExternalContactId($domain['owner_contact_id']);
    if (!$contactId) {
        if ($defaultContactId)
            $contactId = $defaultContactId;
        else
            $contactId = 0;
    }

    $reportItem['contact_id'] = $contactId;

    checkNeededFieldsAndModify($domain, $clientId, $contactId);

    // Check domain tld exist in domain pricing
    $domainLevels = explode('.', $domain['domain']);
    $domainTld = $domainLevels[count($domainLevels) - 1];

    // if price not exist skip domain import
    if (!isset($tldPrices[$domainTld])) {
        $reportItem['error_message'] = ERROR_TLD_NOT_EXIST_IN_DOMAIN_PRICING;
        $reportItem['status'] = ImportStatusType::Skipped;
        $reportCsv->writeRecords([$reportItem], CSV::NoWriteHeaders);
        continue;
    }
    $domain['recurringamount'] = $tldPrices[$domainTld]['renew']['1'];

    // Check that domain not exist
    try {
        $domainExist = Capsule::table(DatabaseTable::Domains)
            ->where('domain', $domain['domain'])
            ->first();

        if ($domainExist) {
            $reportItem['error_message'] = ERROR_DOMAIN_ALREADY_EXIST_IN_DATABASE;
            $reportCsv->writeRecords([$reportItem], CSV::NoWriteHeaders);
            continue;
        }
    } catch (Exception $e) {
        errorMessage(ERROR_CANT_GET_ACCESS_TO_DATABASE);
        $reportItem['error_message'] = ERROR_CANT_GET_ACCESS_TO_DATABASE;
        $reportCsv->writeRecords([$reportItem], CSV::NoWriteHeaders);
        continue;
    }

    // Get userid by clientid
    $userId = '';
    try {
        $userclient = Capsule::table(DatabaseTable::UsersClients)
            ->where('client_id', $clientId)
            ->first();
        if ($userclient)
            $userId = $userclient->auth_user_id;
        else {
            $reportItem['error_message'] = ERROR_CLIENT_HAS_NO_USER;
            $reportCsv->writeRecords([$reportItem], CSV::NoWriteHeaders);
            continue;
        }
    } catch (Exception $e) {
        errorMessage(ERROR_NO_USER_BY_CLIENT_ID, ['message' => $e->getMessage(), 'code' => $e->getCode()]);
        $reportItem['error_message'] = ERROR_NO_USER_BY_CLIENT_ID;
        $reportCsv->writeRecords([$reportItem], CSV::NoWriteHeaders);
        continue;
    }

    // Create order
    $isOrderCreated = true;
    try {
        $insertData = [
            'userid'        => $userId,
            'contactid'     => $contactId,
            'date'          => Carbon::now()->toDateTimeString(),
            'nameservers'   => $domain['nameservers'],
            'orderdata'     => '[]',
            'paymentmethod' => $paymentMethod,
            'status'        => 'Pending',
            'notes'         => 'Created by import',
        ];

        $isOrderCreated = Capsule::table(DatabaseTable::Orders)
            ->insert($insertData);

    } catch (Exception $e) {
        infoMessage(ERROR_CANT_CREATE_ORDER_RECORD, ['message' => $e->getMessage(), 'code' => $e->getCode()]);
        $reportItem['error_message'] = ERROR_CANT_CREATE_ORDER_RECORD;
        $reportCsv->writeRecords([$reportItem], CSV::NoWriteHeaders);
        continue;
    }

    // Get last order id
    $orderId = '';
    if (!$isOrderCreated) {
        errorMessage(ERROR_CANT_GET_LAST_ORDER_RECORD);
        $reportItem['error_message'] = ERROR_CANT_GET_LAST_ORDER_RECORD;
        $reportCsv->writeRecords([$reportItem], CSV::NoWriteHeaders);
        continue;
    }

    try {
        $orderRecord = Capsule::table(DatabaseTable::Orders)
            ->orderBy('date', 'desc')
            ->select('id')
            ->first();

        $orderId = $orderRecord->id;

    } catch (Exception $e) {
        $message = ERROR_CANT_GET_LAST_ORDER_RECORD;

        errorMessage(
            ERROR_CANT_GET_LAST_ORDER_RECORD,
            ['message' => $e->getMessage(), 'code' => $e->getCode()]
        );

        $reportItem['error_message'] = ERROR_CANT_GET_LAST_ORDER_RECORD;
        $reportCsv->writeRecords([$reportItem], CSV::NoWriteHeaders);
        continue;
    }

    // Create domain
    if ($orderId) {
        try {
            $insertDomainData = [
                'userid'             => $userId,
                'orderid'            => $orderId,
                'type'               => 'Register',
                'registrationdate'   => $domain['registrationdate'],
                'domain'             => $domain['domain'],
                'registrationperiod' => '1',
                'expirydate'         => $domain['expirydate'],
                'registrar'          => 'openprovider',
                'idprotection'       => $domain['whois_privacy_enabled'],
                'is_premium'         => $domain['is_premium'],
                'status'             => $domain['status'],
                'recurringamount'    => $domain['recurringamount'],
            ];

            if (!empty($domain['subscription_period'])) {
                $insertDomainData['registrationperiod'] =
                    ceil(intval($domain['subscription_period']) / 12);

                if (intval($domain['subscription_period']) < 12) {
                    $reportItem['error_message'] = WARNING_SUBSCRIPTION_PERIOD_SMALLER_THEN_YEAR;
                }
            }

            // Make offset between expirydate and nextduedate.
            // Offset taken from config file.
            if ($insertDomainData['expirydate']) {
                $insertDomainData['nextduedate'] = Carbon::parse($insertDomainData['expirydate'])
                    ->subDays($nextDueDateOffsetFromExpiryDate);
            }

            $domain = Capsule::table(DatabaseTable::Domains)
                ->insert($insertDomainData);

            // Get last added domain id
            try {
                $lastAddedDomain = Capsule::table(DatabaseTable::Domains)
                    ->orderBy('id', 'desc')
                    ->select('id')
                    ->first();

                $reportItem['domain_id'] = $lastAddedDomain->id;
            } catch (Exception $e) {
                errorMessage(
                    ERROR_CANT_GET_LAST_DOMAIN_RECORD,
                    ['message' => $e->getMessage(), 'code' => $e->getCode()]
                );
            }

            $reportItem['status'] = ImportStatusType::Imported;

            // Accepting order
            $acceptOrder = localApi(WHMCSApiActionType::AcceptOrder, [
                'orderid' => $orderId,
            ]);

            if ($acceptOrder['result'] !== 'success')
                $reportItem['error_message'] = ERROR_CANT_ACCEPT_ORDER;

            $reportCsv->writeRecords([$reportItem], CSV::NoWriteHeaders);
        } catch (Exception $e) {
            errorMessage(
                'error: ',
                ['message' => $e->getMessage(), 'code' => $e->getCode()]
            );

            $reportItem['error_message'] = ERROR_CANT_CREATE_DOMAIN;
            $reportCsv->writeRecords([$reportItem], CSV::NoWriteHeaders);
            continue;
        }
    }
}

$reportCsv->close();

function checkDomainStatusValidity($domainStatus, $validDomainStatuses): bool
{
    if (!in_array($domainStatus, $validDomainStatuses)) {
        return false;
    }

    return true;
}

/**
 * Function return client id
 * by customer id through mapping table.
 * If it is return 0 that mean client id not exist.
 *
 * @param $customerId
 * @return int
 */
function getClientIdByCustomerId($customerId): int
{
    try {
        $mappingRecord = Capsule::table(DatabaseTable::MappingInternalExternalContacts)
            ->where('external_id', $customerId)
            ->where('external_contact_type', ImportExternalContactType::Customer)
            ->where('internal_contact_type', ImportInternalContactType::Client)
            ->first();

        if ($mappingRecord) {
            return $mappingRecord->client_or_contact_id;
        }

        return 0;
    } catch (Exception $e) {
        errorMessage(
            'Something went wrong when we try to find client id in database',
            ['database error message' => $e->getMessage(), 'database error code' => $e->getCode()]
        );
        return 0;
    }
}

/**
 * Function return contact id
 * by external contact id through mapping table.
 * If it is return 0 that mean contact id not exist.
 *
 * @param $externalContactId
 * @return int
 */
function getContactIdByExternalContactId($externalContactId): int
{
    try {
        $mappingRecord = Capsule::table(DatabaseTable::MappingInternalExternalContacts)
            ->where('external_id', $externalContactId)
            ->where('external_contact_type', ImportExternalContactType::Contact)
            ->where('internal_contact_type', ImportInternalContactType::Contact)
            ->first();

        if ($mappingRecord) {
            return $mappingRecord->client_or_contact_id;
        }

        return 0;
    } catch (Exception $e) {
        errorMessage(
            'Something went wrong when we try to find contact id in database',
            ['database error message' => $e->getMessage(), 'database error code' => $e->getCode()]
        );
        return 0;
    }
}

/**
 * Function make $domain right format.
 *
 * @param $domain
 * @param $clientId
 * @param $contactId
 */
function checkNeededFieldsAndModify(&$domain, $clientId, $contactId): void
{
    // Set contact and client ids
    $domain['contactid'] = $contactId;
    $domain['clientid']  = $clientId;

    // Set domain name
    $domain['domain'] = $domain['domain_name_ascii'];

    // Set nameservers
    $domain['nameservers'] = implode(',', explode(';', $domain['domain_nameservers']));

    // Payment method
    $domain['paymentmethod'] = 'mailin';

    // Registration date
    if (!isset($domain['registrationdate']) && isset($domain['creation_date']))
        $domain['registrationdate'] = $domain['creation_date'];

    // Expiration date
    if (!isset($domain['expirydate']) && isset($domain['expiration_date'])) {
        $domain['expirydate'] = $domain['expiration_date'];
    }

    // WHMCS domain status
    switch($domain['domain_status']) {
        case 'DOMAIN_STATUS_PENDING_DELETE':
        case 'DOMAIN_STATUS_REGISTERED_ELSEWHERE':
        case 'DOMAIN_STATUS_UNKNOWN':
            $domain['status'] = 'Pending';
            break;
        case 'DOMAIN_STATUS_QUARANTINE':
            $domain['status'] = 'Redemption';
            break;
        case 'DOMAIN_STATUS_ACTIVE':
            $domain['status'] = 'Active';
            break;
        case 'DOMAIN_STATUS_DELETED':
        case 'DOMAIN_STATUS_ERROR_DOMAININFO':
            $domain['status'] = 'Cancelled';
            break;
    }
}

/**
 * Function show formatted error message.
 *
 * @param $message
 * @param array $args
 */
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

/**
 * Function show formatted info message.
 *
 * @param $message
 * @param array $args
 */
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

