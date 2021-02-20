<?php

namespace OpenProvider\WhmcsRegistrar\scripts;

use Composer\Script\Event;

class UpdateGuzzleNamespace
{
    public static function postUpdate(Event $event)
    {
        $vendorDir = $event->getComposer()->getConfig()->get('vendor-dir');

        if (!is_dir("{$vendorDir}/guzzlehttp")) {
            return;
        }

        $vendorStaticDir = "{$vendorDir}/../vendor-static";
        
        self::takeoutGuzzleFromVendor($vendorDir, $vendorStaticDir);
        self::replaceGuzzleInDirectory("{$vendorStaticDir}/guzzlehttp");
        self::replaceGuzzleInDirectory("{$vendorStaticDir}/openprovider/rest-client-php/src");
    }

    private static function replaceGuzzleInDirectory(string $pathDir)
    {
        if (!is_dir($pathDir)) {
            return;
        }

        $directory = opendir($pathDir);

        while ($element = readdir($directory)) {
            if ($element == '.' || $element == '..') {
                continue;
            }

            if (is_dir("{$pathDir}/{$element}")) {
                self::replaceGuzzleInDirectory("{$pathDir}/{$element}");

                continue;
            }

            $filePath = "{$pathDir}/{$element}";
            $fileContent = file_get_contents($filePath);
            $fileContent = preg_replace('/GuzzleHttp6*/', 'GuzzleHttp6', $fileContent);
            file_put_contents($filePath, $fileContent);
        }
    }

    private static function takeoutGuzzleFromVendor(string $vendorDir, string $vendorStaticDir)
    {
        shell_exec("rm -rf {$vendorStaticDir}/guzzlehttp");
        shell_exec("mkdir --parents {$vendorStaticDir}; mv {$vendorDir}/guzzlehttp {$vendorStaticDir}");
    }
}
