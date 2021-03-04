<?php


namespace OpenProvider;


use OpenProvider\API\APIConfig;
use OpenProvider\API\AutoloadConstructor;

class PlacementPlus extends AutoloadConstructor
{
    const PLACEMENT_PLUS_URL = 'https://api.rns.domains/recommend-domains';

    public $input;
    public $output;

    public static function getSuggestionDomain($domainName, $login, $password)
    {
        $data = [
            'password' => "{$password}",
            "account"=>"{$login}",
            "input"=>"{$domainName}",
            "allowplatinum"=>"0",
            "hints"=>"placementplus",
            "version"=>"3",
            "verbose"=>"1",
        ];

        $url = self::PLACEMENT_PLUS_URL . "?" . http_build_query($data);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, APIConfig::$curlTimeout);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

        $ret = curl_exec($ch);

        curl_close($ch);

        return $ret;
    }
}