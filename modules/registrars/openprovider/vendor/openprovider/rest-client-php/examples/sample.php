<?php

// Include autoloader.
require __DIR__ . '/../vendor/autoload.php';

use Openprovider\Api\Rest\Client\Auth\Model\AuthLoginRequest;
use Openprovider\Api\Rest\Client\Base\Configuration;
use Openprovider\Api\Rest\Client\Client;
use GuzzleHttp\Client as HttpClient;

// Create a new http client.
$httpClient = new HttpClient();

// Create a new configuration.
$configuration = new Configuration();

// Build an api client to use created client & configuration.
$client = new Client($httpClient, $configuration);

// Credentials;
$username = 'name';
$password = 'pass';

// Retrieve a token for further usage.
$loginResult = $client->getAuthModule()->getAuthApi()->login(
    new AuthLoginRequest([
        'username' => $username,
        'password' => $password,
    ])
);

// Add a token to configuration (it will update the $client).
$configuration->setAccessToken($loginResult->getData()->getToken());

// Use this client to make API calls.
$result = $client->getTldModule()->getTldServiceApi()->getTld('com');

// Operate with the result.
print_r($result);
