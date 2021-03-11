<?php

namespace OpenProvider;

use OpenProvider\API\APIConfig;
use OpenProvider\API\AutoloadConstructor;

class PlacementPlus extends AutoloadConstructor
{
    const PLACEMENT_PLUS_URL = 'https://api.rns.domains/recommend-domains';

    /**
     * @var string
     */
    public string $input;
    /**
     * @var string
     */
    public string $output;

    /**
     * @param $domainName
     * @param $login
     * @param $password
     * @return array
     */
    public static function getSuggestionDomain($domainName, $login, $password): array
    {
        $data = [
            'password'      => "{$password}",
            'account'       => "{$login}",
            'input'         => "{$domainName}",
            'allowplatinum' => '0',
            'hints'         => 'placementplus',
            'version'       => '3',
            'verbose'       => '1',
        ];

        $httpBuildQueryData = http_build_query($data);

        $url = self::PLACEMENT_PLUS_URL . '?' . $httpBuildQueryData;

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, APIConfig::$curlTimeout);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

        $ret = curl_exec($ch);

        $errNumber = curl_errno($ch);
        $errMessage = curl_error($ch);

        curl_close($ch);

        logModuleCall(
            'OpenProvider NL',
            self::PLACEMENT_PLUS_URL,
            array(
                'requestBody' => $data,
            ),
            array(
                'curlResponse' => $ret,
                'curlErrNo'    => $errNumber,
                'errorMessage' => $errMessage,
            ),
            null,
            array(
                $password,
                htmlentities($password)
            )
        );

        return json_decode($ret, true);
    }
}
