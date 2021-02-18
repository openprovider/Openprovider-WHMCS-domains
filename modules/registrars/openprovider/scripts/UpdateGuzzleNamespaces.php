<?php

namespace OpenProvider\WhmcsRegistrar\scripts;

use Composer\Script\Event;

class UpdateGuzzleNamespaces
{
    public static function postUpdate(Event $event)
    {
        $vendorDir = $event->getComposer()->getConfig()->get('vendor-dir');

        $guzzleRootPath = "$vendorDir/guzzlehttp";

        $openproviderDir = "$vendorDir/openprovider";

        $openproviderRestClientApiDir = "$openproviderDir/rest-client-php/src";


        if (is_dir($guzzleRootPath))
            self::replaceGuzzleInDirectory($guzzleRootPath);
        if (is_dir($openproviderRestClientApiDir))
            self::replaceGuzzleInDirectory($openproviderRestClientApiDir);
    }

    private static function replaceGuzzleInDirectory(string $pathDir)
    {
        $directory = opendir($pathDir);

        while ($element = readdir($directory))
        {
            if ($element == '.' || $element == '..')
                continue;
            if (is_dir("$pathDir/$element")) {
                self::replaceGuzzleInDirectory("$pathDir/$element");
                continue;
            }

            $filePath = "$pathDir/$element";
            $fileContent = file_get_contents($filePath);
            $fileContent = preg_replace('/GuzzleHttp6*/', 'GuzzleHttp6', $fileContent);
            file_put_contents($filePath, $fileContent);
        }
    }
}