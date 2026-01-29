<?php

namespace OpenProvider\API;

use GuzzleHttp6\Promise\PromiseInterface;

interface ApiCallerInterface
{
    /**
     * @param string $cmd
     * @param array $args
     * @return ResponseInterface
     */
    public function call(string $cmd, array $args = []): ResponseInterface;

    /**
     * @param string $cmd
     * @param array $args
     * @return \GuzzleHttp6\Promise\PromiseInterface
     */
    public function callAsync(string $cmd, array $args = []): PromiseInterface;
}
