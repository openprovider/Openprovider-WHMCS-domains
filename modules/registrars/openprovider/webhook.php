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

$expectedApiKey = getenv('OPENPROVIDER_WEBHOOK_API_KEY');

if (!is_string($expectedApiKey) || $expectedApiKey === '') {
    http_response_code(500);
    exit('Webhook API key is not configured.');
}

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

if ($rawBody === false || trim($rawBody) === '') {
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

$eventType = (string) $payload['eventType'];

if ($eventType === 'testEvent') {
    logModuleCall(
        'openprovider webhooks',
        'Webhook Connection Test Successful',
        $rawBody,
        [
            'success' => true,
            'message' => 'Webhook connection established successfully',
        ],
        [
            'webhookId' => $payload['id'],
            'schemaVersion' => $payload['schemaVersion'],
            'eventType' => $eventType,
            'timeStamp' => $payload['timeStamp'],
        ],
        [$expectedApiKey]
    );

    http_response_code(200);

    echo json_encode([
        'success' => true,
        'message' => 'Webhook connection established successfully',
        'eventType' => $eventType,
    ]);

    exit;
}

$supportedEvents = [
    'outgoingTransferCompleted',
    'deletionCompleted',
];

if (!in_array($eventType, $supportedEvents, true)) {
    logModuleCall(
        'openprovider webhooks',
        'Unsupported Webhook Event',
        $rawBody,
        [
            'success' => true,
            'message' => 'Webhook acknowledged, but event type is not supported',
        ],
        [
            'webhookId' => $payload['id'],
            'eventType' => $eventType,
            'domain' => $payload['data']['domain'] ?? null,
        ],
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

if (
    !isset($payload['data']['domain'])
    || !is_string($payload['data']['domain'])
    || trim($payload['data']['domain']) === ''
) {
    http_response_code(400);

    echo json_encode([
        'success' => false,
        'message' => 'Missing domain',
    ]);

    exit;
}

$domainName = strtolower(trim($payload['data']['domain']));

try {
    $domain = Capsule::table('tbldomains')
        ->whereRaw('LOWER(domain) = ?', [$domainName])
        ->first();

    if ($domain === null) {
        logModuleCall(
            'openprovider webhooks',
            'Webhook Domain Not Found',
            $rawBody,
            [
                'success' => true,
                'message' => 'Domain was not found in WHMCS',
            ],
            [
                'webhookId' => $payload['id'],
                'eventType' => $eventType,
                'domain' => $domainName,
                'openproviderDomainId' => $payload['data']['domainId'] ?? null,
            ],
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

    $newStatus = $statusMap[$eventType];
    $oldStatus = $domain->status;

    if ($oldStatus === $newStatus) {
        logModuleCall(
            'openprovider webhooks',
            'Webhook Domain Status Unchanged',
            $rawBody,
            [
                'success' => true,
                'message' => 'Domain already has the required status',
            ],
            [
                'webhookId' => $payload['id'],
                'eventType' => $eventType,
                'domain' => $domainName,
                'whmcsDomainId' => $domain->id,
                'currentStatus' => $oldStatus,
            ],
            [$expectedApiKey]
        );

        http_response_code(200);

        echo json_encode([
            'success' => true,
            'message' => 'Webhook processed successfully; domain status was unchanged',
            'eventType' => $eventType,
            'domain' => $domainName,
            'oldStatus' => $oldStatus,
            'newStatus' => $newStatus,
        ]);

        exit;
    }

    Capsule::table('tbldomains')
        ->where('id', $domain->id)
        ->update([
            'status' => $newStatus,
        ]);

    logModuleCall(
        'openprovider webhooks',
        'Webhook Domain Status Updated',
        $rawBody,
        [
            'success' => true,
            'message' => 'Domain status updated successfully',
        ],
        [
            'webhookId' => $payload['id'],
            'eventType' => $eventType,
            'domain' => $domainName,
            'openproviderDomainId' => $payload['data']['domainId'] ?? null,
            'whmcsDomainId' => $domain->id,
            'oldStatus' => $oldStatus,
            'newStatus' => $newStatus,
        ],
        [$expectedApiKey]
    );

    http_response_code(200);

    echo json_encode([
        'success' => true,
        'message' => 'Webhook processed successfully',
        'eventType' => $eventType,
        'domain' => $domainName,
        'oldStatus' => $oldStatus,
        'newStatus' => $newStatus,
    ]);
} catch (Throwable $exception) {
    logModuleCall(
        'openprovider webhooks',
        'Webhook Processing Error',
        $rawBody,
        [
            'success' => false,
            'message' => $exception->getMessage(),
        ],
        [
            'webhookId' => $payload['id'] ?? null,
            'eventType' => $eventType,
            'domain' => $domainName ?? null,
            'exception' => get_class($exception),
        ],
        [$expectedApiKey]
    );

    http_response_code(500);

    echo json_encode([
        'success' => false,
        'message' => 'An unexpected error occurred while processing the webhook',
    ]);
}