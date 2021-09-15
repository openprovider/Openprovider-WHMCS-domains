<?php

namespace OpenProvider\WhmcsRegistrar\scripts;

use Composer\Script\Event;

class UpdateIlluminateSupportNamespace
{
    const LibraryPathInVendor = 'illuminate/support';
    const SearchingNamespace = 'Illuminate\Support';
    const CustomNamespace = 'Illuminate\\Support6';

    public static function postUpdate(Event $event)
    {
        $vendorDir = $event->getComposer()->getConfig()->get('vendor-dir');

        if (!is_dir($vendorDir . '/' . self::LibraryPathInVendor)) {
            return;
        }

        $vendorStaticDir = "{$vendorDir}/../vendor-static";

        self::takeoutIlluminateSupportFromVendor($vendorDir, $vendorStaticDir);
        self::replaceIlluminateNamespaceInDirectory($vendorStaticDir);
        self::replaceIlluminateNamespaceInDirectory(sprintf('%s/illuminate', $vendorDir));
        self::replaceIlluminateNamespaceInDirectory(sprintf('%s/../import', $vendorDir));
    }

    private static function replaceIlluminateNamespaceInDirectory(string $pathDir)
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
                self::replaceIlluminateNamespaceInDirectory("{$pathDir}/{$element}");

                continue;
            }

            $filePath = "{$pathDir}/{$element}";
            $fileContent = file_get_contents($filePath);
            $fileContent = preg_replace('/Illuminate\\\Support6*/', 'Illuminate\Support6', $fileContent);
            file_put_contents($filePath, $fileContent);
        }
    }

    private static function takeoutIlluminateSupportFromVendor(string $vendorDir, string $vendorStaticDir)
    {
        shell_exec(sprintf("rm -rf %s/%s", $vendorStaticDir, self::LibraryPathInVendor));
        shell_exec(sprintf("mkdir --parents %s/%s; mv %s/%s %s/%s/../",
            $vendorStaticDir, self::LibraryPathInVendor, $vendorDir,
            self::LibraryPathInVendor, $vendorStaticDir, self::LibraryPathInVendor));
    }
}
