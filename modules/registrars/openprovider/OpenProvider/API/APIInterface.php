<?php

namespace OpenProvider\API;

interface APIInterface
{
    public function call(string $method, array $args = []);
}
