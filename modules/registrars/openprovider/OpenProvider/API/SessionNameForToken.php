<?php

namespace OpenProvider\API;

class SessionNameForToken
{
    /**
     * @param string $username
     * @param string $password
     * @param string $host
     * @return string
     */
    public static function encode(string $username, string $password, string $host): string
    {
        return substr(md5($username . $password . $host), 0, -2);
    }
}
