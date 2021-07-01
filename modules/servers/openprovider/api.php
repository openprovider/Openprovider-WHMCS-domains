<?php

use OpenProvider\API\ApiV1;
use OpenProvider\Logger;
use Symfony\Component\Serializer\NameConverter\CamelCaseToSnakeCaseNameConverter;

const OPENPROVIDER_HOST = 'https://api.cte.openprovider.eu';

function getApi($username, $password): ?ApiV1
{
    $api = new ApiV1(new Logger(), new CamelCaseToSnakeCaseNameConverter, new idna_convert);

    $api->getConfiguration()->setHost(OPENPROVIDER_HOST);

    $tokenRequest = $api->call('generateAuthTokenRequest', [
        'username' => $username,
        'password' => $password
    ]);

    if ($tokenRequest->getCode() != 0) {
        return null;
    }

    $token = $tokenRequest->getData()['token'];

    $api->getConfiguration()->setToken($token);

    return $api;
}
