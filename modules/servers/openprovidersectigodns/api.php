<?php

use Carbon\Carbon;
use OpenProvider\API\ApiV1;
use OpenProvider\Logger;
use Symfony\Component\Serializer\NameConverter\CamelCaseToSnakeCaseNameConverter;
use WHMCS\Database\Capsule;

const OPENPROVIDER_HOST = 'https://api.openprovider.eu';

function getApi($username, $password): ?ApiV1
{
    $api = new ApiV1(new Logger(), new CamelCaseToSnakeCaseNameConverter, new idna_convert);
    $api->getConfiguration()->setHost(OPENPROVIDER_HOST);
    $token = '';
    $token_result = [];

    if (Capsule::schema()->hasTable('reseller_tokens')) {
        $token_result = Capsule::table('reseller_tokens')->where('username', $username)->orderBy('created_at', 'desc')->get();
    }

    $expireTime = count($token_result) > 0 ? new Carbon($token_result[0]->expire_at) : null;

    if (count($token_result) > 0 && Carbon::now()->diffInSeconds($expireTime, false) > 0) {
        $token = $token_result[0]->token;
    } else {

        $tokenRequest = $api->call('generateAuthTokenRequest', [
            'username' => $username,
            'password' => $password
        ]);

        if ($tokenRequest->getCode() != 0) {
            return null;
        }

        $token = $tokenRequest->getData()['token'];
    }

    $api->getConfiguration()->setToken($token);

    return $api;
}
