<?php

namespace OpenProvider\API;

interface ApiInterface
{
    /**
     * @param string $cmo
     * @param array $args
     * @return mixed
     */
    public function call(string $cmo, array $args = []);
}
