<?php

require_once(__DIR__ . '/../../../init.php');

use WHMCS\Database\Capsule;

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);

    echo json_encode([
        'success' => false,
        'message' => 'Method Not Allowed',
    ]);

    exit;
}

$expectedApiKey = 'test-webhook-api-key';

$headers = function_exists('getallheaders') ? getallheaders() : [];

$authorizationHeader =
    $_SERVER['HTTP_AUTHORIZATION']
    ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION']
    ?? $headers['Authorization']
    ?? $headers['authorization']
    ?? '';

if ($authorizationHeader === '') {
    http_response_code(401);

    echo json_encode([
        'success' => false,
        'message' => 'Missing Bearer token',
    ]);

    exit;
}

if (!preg_match('/^Bearer\s+(.+)$/i', $authorizationHeader, $matches)) {
    http_response_code(401);

    echo json_encode([
        'success' => false,
        'message' => 'Invalid Authorization header',
    ]);

    exit;
}

$providedApiKey = trim($matches[1]);

if (!hash_equals($expectedApiKey, $providedApiKey)) {
    http_response_code(401);

    echo json_encode([
        'success' => false,
        'message' => 'Invalid Bearer token',
    ]);

    exit;
}

$rawBody = file_get_contents('php://input');

if ($rawBody === false || $rawBody === '') {
    http_response_code(400);

    echo json_encode([
        'success' => false,
        'message' => 'Empty request body',
    ]);

    exit;
}

$payload = json_decode($rawBody, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(400);

    echo json_encode([
        'success' => false,
        'message' => 'Invalid JSON payload',
    ]);

    exit;
}

$requiredFields = [
    'id',
    'schemaVersion',
    'eventType',
    'timeStamp',
    'data',
];

foreach ($requiredFields as $field) {
    if (!array_key_exists($field, $payload)) {
        http_response_code(400);

        echo json_encode([
            'success' => false,
            'message' => "Missing required field: {$field}",
        ]);

        exit;
    }
}

if (!is_array($payload['data'])) {
    http_response_code(400);

    echo json_encode([
        'success' => false,
        'message' => 'Invalid data field',
    ]);

    exit;
}

if (empty($payload['data']['domain'])) {
    http_response_code(400);

    echo json_encode([
        'success' => false,
        'message' => 'Missing domain',
    ]);

    exit;
}

$supportedEvents = [
    'testEvent',
    'outgoingTransferCompleted',
    'deletionCompleted',
];

$eventType = $payload['eventType'];

if (!in_array($eventType, $supportedEvents, true)) {
    logModuleCall(
        'openprovider',
        'Unsupported Webhook Event',
        $rawBody,
        [
            'eventType' => $eventType,
            'domain' => $payload['data']['domain'],
        ],
        [],
        [$expectedApiKey]
    );

    http_response_code(200);

    echo json_encode([
        'success' => true,
        'message' => 'Webhook acknowledged, but event type is not supported',
        'eventType' => $eventType,
    ]);

    exit;
}

$domainName = strtolower(trim($payload['data']['domain']));

$domain = Capsule::table('tbldomains')
    ->whereRaw('LOWER(domain) = ?', [$domainName])
    ->first();

if ($domain === null) {
    logModuleCall(
        'openprovider',
        'Webhook Domain Not Found',
        $rawBody,
        [
            'eventType' => $eventType,
            'domain' => $domainName,
            'openproviderDomainId' => $payload['data']['domainId'] ?? null,
        ],
        [],
        [$expectedApiKey]
    );

    http_response_code(200);

    echo json_encode([
        'success' => true,
        'message' => 'Webhook acknowledged, but domain was not found in WHMCS',
        'eventType' => $eventType,
        'domain' => $domainName,
    ]);

    exit;
}

$statusMap = [
    'outgoingTransferCompleted' => 'Transferred Away',
    'deletionCompleted' => 'Cancelled',
];

$newStatus = $statusMap[$eventType] ?? null;

if ($newStatus !== null && $domain->status !== $newStatus) {
    Capsule::table('tbldomains')
        ->where('id', $domain->id)
        ->update([
            'status' => $newStatus,
        ]);

    logModuleCall(
        'openprovider',
        'Webhook Domain Status Updated',
        $rawBody,
        [
            'eventType' => $eventType,
            'domain' => $domainName,
            'whmcsDomainId' => $domain->id,
            'oldStatus' => $domain->status,
            'newStatus' => $newStatus,
        ],
        [],
        [$expectedApiKey]
    );
}

if ($newStatus === null || $domain->status === $newStatus) {
    logModuleCall(
        'openprovider',
        'Webhook Domain Status Unchanged',
        $rawBody,
        [
            'eventType' => $eventType,
            'domain' => $domainName,
            'whmcsDomainId' => $domain->id,
            'currentStatus' => $domain->status,
        ],
        [],
        [$expectedApiKey]
    );
}

// logModuleCall(
//     'openprovider',
//     'Webhook Domain Found',
//     $rawBody,
//     [
//         'eventType' => $eventType,
//         'domain' => $domainName,
//         'openproviderDomainId' => $payload['data']['domainId'] ?? null,
//         'whmcsDomainId' => $domain->id,
//         'currentStatus' => $domain->status,
//     ],
//     [],
//     [$expectedApiKey]
// );

http_response_code(200);

echo json_encode([
    'success' => true,
    'message' => 'Webhook processed successfully',
    'eventType' => $eventType,
    'domain' => $domainName,
    'oldStatus' => $domain->status,
    'newStatus' => $newStatus ?? $domain->status,
]);