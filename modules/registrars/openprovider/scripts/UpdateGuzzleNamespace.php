<?php

namespace OpenProvider\WhmcsRegistrar\scripts;

use Composer\Script\Event;

class UpdateGuzzleNamespace
{
    public static function postUpdate(Event $event)
    {
        $vendorDir = $event->getComposer()->getConfig()->get('vendor-dir');

        $vendorStaticDir = "{$vendorDir}/../vendor-static";
        
        if (!is_dir("{$vendorDir}/guzzlehttp")) {
            return;
        }
        
        self::replaceGuzzleInDirectory("{$vendorDir}/guzzlehttp");
        self::replaceGuzzleInDirectory("{$vendorDir}/openprovider");

        self::removeDir("{$vendorStaticDir}/guzzlehttp");
        self::moveDir("{$vendorDir}/guzzlehttp", $vendorStaticDir);
    }

    private static function replaceGuzzleInDirectory(string $pathDir)
    {
        $directory = opendir($pathDir);

        while ($element = readdir($directory))
        {
            if ($element == '.' || $element == '..'){
                continue;
            }
            
            if (is_dir("{$pathDir}/{$element}")) {
                self::replaceGuzzleInDirectory("{$pathDir}/{$element}");
                continue;
            }

            $filePath = "{$pathDir}/{$element}";
            $fileContent = file_get_contents($filePath);
            $replacedFileContent = preg_replace('/GuzzleHttp6*/', 'GuzzleHttp6', $fileContent);
            file_put_contents($filePath, $replacedFileContent);
        }
    }

    private static function moveDir(string $pathFrom, string $pathTo)
    {
        shell_exec("mkdir --parents {$pathTo}; mv {$pathFrom} {$pathTo}");
    }

    private static function removeDir(string $path)
    {
        shell_exec("rm -rf {$path}");
    }
}