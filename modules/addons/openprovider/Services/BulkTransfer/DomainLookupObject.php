<?php

namespace OpenProvider\WhmcsDomainAddon\Services\BulkTransfer;

class DomainLookupObject
{
    /**
     * @var string
     */
    protected $secondLevel;

    /**
     * @var string
     */
    protected $topLevel;

    public function __construct($secondLevel, $topLevel)
    {
        $this->secondLevel = $secondLevel;
        $this->topLevel = $topLevel;
    }

    public static function fromDomain($domain)
    {
        $parts = explode('.', trim((string) $domain), 2);

        if (count($parts) !== 2 || $parts[0] === '' || $parts[1] === '') {
            throw new \InvalidArgumentException('Invalid domain name provided.');
        }

        return new self($parts[0], $parts[1]);
    }

    public function getSecondLevel()
    {
        return $this->secondLevel;
    }

    public function getTopLevel()
    {
        return $this->topLevel;
    }

    public function getDomain()
    {
        return $this->secondLevel . '.' . $this->topLevel;
    }
}
