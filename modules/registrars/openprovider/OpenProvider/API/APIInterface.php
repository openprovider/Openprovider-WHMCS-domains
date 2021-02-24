<?php

namespace OpenProvider\API;

interface APIInterface
{
    public function sendRequest($method, $args = null);

    public function setParams($params, $debug = 0);
}