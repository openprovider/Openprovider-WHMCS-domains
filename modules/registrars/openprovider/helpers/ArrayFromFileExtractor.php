<?php

namespace OpenProvider\WhmcsRegistrar\helpers;

class ArrayFromFileExtractor
{
    public const PUNY_CODE_CC_TLDS_PATH = '/configuration/puny-code-cc-tlds.php';
    public const TLD_WHICH_NEEDED_SHORT_STATE_PATH = '/configuration/tld-which-needed-short-state.php';

    /**
     * @var string
     */
    private $modulePath;

    /**
     * ArrayFromFileExtractor constructor.
     * @param string $modulePath
     */
    public function __construct(string $modulePath)
    {
        $this->modulePath = $modulePath;
    }

    /**
     * @param string $path
     * @return array
     */
    public function extract(string $path): array
    {
        $filePath = realpath($this->modulePath . $path);
        if (!file_exists($filePath)) {
            return [];
        }

        return include $filePath;
    }
}
