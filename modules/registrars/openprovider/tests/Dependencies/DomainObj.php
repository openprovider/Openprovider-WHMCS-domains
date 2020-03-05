<?php

namespace WHMCS\Domains\DomainLookup;

/**
 * Class DomainObj
 * @package WHMCS\Domains\DomainLookup
 */
class DomainObj
{
    protected $secondLevel;
    protected $topLevel;

    /**
     * @return mixed
     */
    public function getSecondLevel()
    {
        return $this->secondLevel;
    }

    /**
     * @param mixed $secondLevel
     */
    public function setSecondLevel($secondLevel)
    {
        $this->secondLevel = $secondLevel;
    }

    /**
     * @return mixed
     */
    public function getTopLevel()
    {
        return $this->topLevel;
    }

    /**
     * @param mixed $topLevel
     */
    public function setTopLevel($topLevel)
    {
        $this->topLevel = $topLevel;
    }

}