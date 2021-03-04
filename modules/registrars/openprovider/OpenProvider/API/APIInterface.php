<?php

namespace OpenProvider\API;

interface APIInterface
{
    public function sendRequest(string $method, array|null $args = null);
}