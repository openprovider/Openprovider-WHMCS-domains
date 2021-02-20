<?php

// Include autoloader.
require __DIR__ . '/../vendor/autoload.php';

use Openprovider\Api\Rest\Client\Auth\Model\AuthLoginRequest;
use Openprovider\Api\Rest\Client\Base\ApiException;
use Openprovider\Api\Rest\Client\Base\Configuration;
use Openprovider\Api\Rest\Client\Client;
use GuzzleHttp\Client as HttpClient;

// Create new http client.
$httpClient = new HttpClient();

// Create new configuration.
$configuration = new Configuration();

// Build api client for using created client & configuration.
$client = new Client($httpClient, $configuration);

// Our credentials.
$username = 'user';
$password = 'pass';

// Retrieve token for further using.
$loginResult = $client->getAuthModule()->getAuthApi()->login(
    new AuthLoginRequest([
        'username' => $username,
        'password' => $password,
    ])
);

// Set token to configuration (it will update the $client).
$configuration->setAccessToken($loginResult->getData()->getToken());

// Access token will expire after some time so we will have to relogin in this case.
$tryApi = function (callable $callback) use ($username, $password, $client, $configuration) {
    try {
        // Call the client.
        return $callback();
    } catch (ApiException $e) {
        // Decode response body (json is expected).
        $response = json_decode($e->getResponseBody(), true);

        // Rethrow $e only if its not a "failed auth" exception.
        if (!$response || $response['code'] !== 196) {
            throw $e;
        };

        // Receive a new token.
        $loginResult = $client->getAuthModule()->getAuthApi()->login(
            new AuthLoginRequest([
                'username' => $username,
                'password' => $password,
            ])
        );

        // Set token to configuration (it will update the $client).
        $configuration->setAccessToken($loginResult->getData()->getToken());

        // Retry to call the client.
        return $callback();
    }
};

// Calling our api using the "relogin" wrapper.
$tryApi(function () use ($client) {
    // Use this client for API calls.
    $result = $client->getTldModule()->getTldServiceApi()->getTld('com');

    // Operate with the result.
    print_r($result);
});
