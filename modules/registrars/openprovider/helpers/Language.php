<?php


namespace OpenProvider\WhmcsRegistrar\helpers;

use OpenProvider\WhmcsRegistrar\src\Configuration;

class Language
{
    const PAGE_DNSSEC = 'dnssec';


    private static $lang = 'english';

    private static $contentFolder = '/../lang/';

    public static function setLang(string $lang)
    {
        self::$lang = mb_strtolower($lang);
    }

    public static function getLang()
    {
        return self::$lang;
    }

    public static function getContent(string $page)
    {
        $contentPath = realpath(__DIR__ . self::$contentFolder . self::$lang . '.php');

        if (file_exists($contentPath)) {
            include_once $contentPath;

            // $lang variable takes from included file ../lang/*
            return $lang[mb_strtolower($page)];
        }

        return [];
    }
}