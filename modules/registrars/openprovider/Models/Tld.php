<?php


namespace OpenProvider\WhmcsRegistrar\Models;


class Tld
{
    private string $tld;

    public function __construct(string $tld)
    {
        $this->tld = $tld;
    }

    public function get(): string
    {
        return $this->tld;
    }

    public function isNeededShortState(): bool
    {
        $tlds = $this->_getTldWhichNeededShortState();
        if (count($tlds) > 0)
            return in_array($this->tld, $tlds);

        return false;
    }

    private function _getTldWhichNeededShortState(): array
    {
        $configFilepath = realpath(__DIR__ . '/../configuration/tld-which-needed-short-state.php');
        if (!file_exists($configFilepath))
            return [];

        return include $configFilepath;
    }
}