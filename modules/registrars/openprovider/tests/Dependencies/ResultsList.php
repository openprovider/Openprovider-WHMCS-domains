<?php

namespace WHMCS\Domains\DomainLookup;

/**
 * Class ResultsList
 * @package WHMCS\Domains\DomainLookup
 */
class ResultsList
{
    public $result;

    public function append($append)
    {
        $this->result[] = $append;
    }
}