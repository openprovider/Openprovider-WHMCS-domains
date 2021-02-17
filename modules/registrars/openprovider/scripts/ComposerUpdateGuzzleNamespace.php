<?php

namespace OpenProvider\WhmcsRegistrar\scripts;

use Composer\Script\Event;

class ComposerUpdateGuzzleNamespace
{
    public static function execute(Event $event)
    {
        $vendorDir = $event->getComposer()->getConfig()->get('vendor-dir');

        $guzzleRootPath = "$vendorDir/guzzlehttp";

        $openproviderDir = "$vendorDir/openprovider";


        $guzzleDir = "$guzzleRootPath/guzzle/src";
        $guzzlePromisesDir = "$guzzleRootPath/promises/src";
        $guzzlePsr7Dir = "$guzzleRootPath/psr7/src";

        $openproviderRestClientApiDir = "$openproviderDir/rest-client-php/src";


        self::replaceGuzzleInDirectory($guzzleDir);
        self::replaceGuzzleInDirectory($guzzlePromisesDir);
        self::replaceGuzzleInDirectory($guzzlePsr7Dir);
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