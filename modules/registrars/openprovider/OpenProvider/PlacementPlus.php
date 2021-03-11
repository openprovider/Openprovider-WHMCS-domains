<?php

namespace OpenProvider;

use OpenProvider\API\APIConfig;
use OpenProvider\API\AutoloadConstructor;
use OpenProvider\WhmcsRegistrar\src\Configuration;

class PlacementPlus extends AutoloadConstructor
{
    const PLACEMENT_PLUS_URL = 'https://api.rns.domains/recommend-domains';
    const ALLOW_PLATINUM = '0';
    const HINTS = 'placementplus';
    const VERSION = '3';
    const VERBOSE = '1';

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
     * @return array
     */
    public static function getSuggestionDomain($domainName): array
    {
        $data = [
            'password'      => Configuration::get('placementPlusPassword'),
            'account'       => Configuration::get('placementPlusAccount'),
            'input'         => "{$domainName}",
            'allowplatinum' => self::ALLOW_PLATINUM,
            'hints'         => self::HINTS,
            'version'       => self::VERSION,
            'verbose'       => self::VERBOSE,
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
            [
                'requestBody' => $data,
            ],
            [
                'curlResponse' => $ret,
                'curlErrNo'    => $errNumber,
                'errorMessage' => $errMessage,
            ],
            null,
            [
                Configuration::get('placementPlusPassword'),
                htmlentities(Configuration::get('placementPlusPassword'))
            ]
        );

        return json_decode($ret, true);
    }

    /**
     * @return bool
     */
    public static function isCredentialExist(): bool
    {
        return Configuration::get('placementPlusAccount') && Configuration::get('placementPlusPassword');
    }
}
