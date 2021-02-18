<?php

namespace OpenProvider\WhmcsRegistrar\scripts;

use Composer\Script\Event;

class UpdateGuzzleNamespace
{
    public static function postUpdate(Event $event)
    {
        $vendorDir = $event->getComposer()->getConfig()->get('vendor-dir');

        // replacing guzzle namespaces
        $guzzleHttpFolder = "guzzlehttp";

        $guzzleRootPath = "$vendorDir/$guzzleHttpFolder";

        $openproviderDir = "$vendorDir/openprovider";

        $openproviderRestClientApiDir = "$openproviderDir/rest-client-php/src";


        if (is_dir($guzzleRootPath))
            self::replaceGuzzleInDirectory($guzzleRootPath);
        if (is_dir($openproviderRestClientApiDir))
            self::replaceGuzzleInDirectory($openproviderRestClientApiDir);

        // replacing guzzle to vendor-static
        $vendorStaticDir = "$vendorDir/../vendor-static";
        $newGuzzleDir = "$vendorStaticDir/$guzzleHttpFolder";

        self::removeDir($newGuzzleDir);
        self::moveDir($guzzleRootPath, $vendorStaticDir);
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

    private static function moveDir(string $pathFrom, string $pathTo)
    {
        shell_exec("mkdir --parents $pathTo; mv $pathFrom $pathTo");
    }

    private static function removeDir(string $path)
    {
        shell_exec("rm -rf $path");
    }
}