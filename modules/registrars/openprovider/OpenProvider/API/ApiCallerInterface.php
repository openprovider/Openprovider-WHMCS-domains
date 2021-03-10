<?php

namespace OpenProvider\API;

interface ApiCallerInterface
{
    /**
     * @param string $cmd
     * @param array $args
     * @return ResponseInterface
     */
    public function call(string $cmd, array $args = []): ResponseInterface;
}
