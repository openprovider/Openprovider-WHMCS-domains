<?php


namespace OpenProvider\WhmcsRegistrar\helpers;


class Dictionary
{
    private static $dictionaryFolder = '/../dictionaries/';

    const USStates = 'us-states.php';

    public static function get(string $dictionary): array
    {
           if (!$dictionary)
               return [];

           $dictionaryPath = realpath(__DIR__ . self::$dictionaryFolder . $dictionary);
           if (!file_exists($dictionaryPath))
               return [];

           $dictionary = include $dictionaryPath;

           array_walk($dictionary, function (&$key, $value) {
               $key = ucwords($key);
           });
           return $dictionary;
    }
}
