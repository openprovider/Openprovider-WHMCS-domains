<?php
namespace WeDevelopCoffee\wPower\Core;

class API
{
    /**
     * Simple API wrapper for the localapi feature. Allows better testing and mocking.
     *
     * @param $command
     * @param array $values
     * @param null $adminuser
     * @return mixed
     */
    public function exec($command, $values = [], $adminuser = null)
    {
        return localAPI($command, $values, $adminuser);
    }
}
