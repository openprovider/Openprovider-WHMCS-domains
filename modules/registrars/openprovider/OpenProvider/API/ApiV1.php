<?php

namespace OpenProvider\API;

class ApiV1 implements ApiInterface
{
    /**
     * @param string $cmd
     * @param array $args
     * @return Response
     */
    public function call(string $cmd, array $args = []): Response
    {
        return new Response();
    }
}
