<?php

// Include autoloader.
require __DIR__ . '/../vendor/autoload.php';

use Openprovider\Api\Rest\Client\Auth\Model\AuthLoginRequest;
use Openprovider\Api\Rest\Client\Base\Configuration;
use Openprovider\Api\Rest\Client\Client;
use GuzzleHttp\Client as HttpClient;

// Create new http client.
$httpClient = new HttpClient();

// Create new configuration.
$configuration = new Configuration();

// Build api client for using created client & configuration.
$client = new Client($httpClient, $configuration);

// Our credentials;
$username = 'name';
$password = 'padd';

// Retrieve token for further using.
$loginResult = $client->getAuthModule()->getAuthApi()->login(
    new AuthLoginRequest([
        'username' => $username,
        'password' => $password,
    ])
);

// Set token to configuration (it will update the $client).
$configuration->setAccessToken($loginResult->getData()->getToken());

// Use this client for API calls.
$result = $client->getTldModule()->getTldServiceApi()->getTld('com');

// Operate with the result.
print_r($result);
