# rest-client-php

> PHP client for Openprovider API

Description
-----------

This software is a PHP client to operate with the [Openprovider API](https://github.com/openprovider/api-documentation).

At this time the API is stable enough to be used, however please note that during the beta phase we may still make breaking changes.

Please use [v1beta branch/version](https://github.com/openprovider/rest-client-php/tree/v1beta).


Usage
-----------
1. If you do not have composer.json in your project folder, create it with the command
	```bash
	composer init
	```
2. Set composer minimum stability to `dev`  
	```bash
	composer config minimum-stability dev
	```
3. Include this package as any other PHP library: 
	```bash
	composer require openprovider/rest-client-php:dev-v1beta
	```
4. Access API via the `Client` class:
	```php
	<?php
	
	// Include autoloader.
	require __DIR__ . '/vendor/autoload.php';
	
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
	
	// Use this client for API calls.
	$result = $client->getTldModule()->getTldServiceApi()->getTld('com');
	
	// Operate with the result.
	print_r($result);
	```
5. Check the ./examples directory for more complex examples.
