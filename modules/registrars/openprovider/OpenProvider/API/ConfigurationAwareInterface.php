<?php

namespace OpenProvider\API;

interface ConfigurationAwareInterface
{
    /**
     * @return ConfigurationInterface
     */
    public function getConfiguration(): ConfigurationInterface;
}
