<?php

namespace OpenProvider\API;

class AccessToken
{
    private const TOKEN_SESSION_NAME = 'ACCESS_TOKEN';

    /**
     * @return string
     */
    public static function getToken(): string
    {
        session_start();

        if (!isset($_SESSION[self::TOKEN_SESSION_NAME])) {
            return '';
        }

        return $_SESSION[self::TOKEN_SESSION_NAME];
    }

    /**
     * @param string $token
     * @return void
     */
    public static function setToken(string $token): void
    {
        session_start();

        $_SESSION[self::TOKEN_SESSION_NAME] = $token;
    }

    /**
     * @return void
     */
    public static function clearToken(): void
    {
        session_start();

        unset($_SESSION[self::TOKEN_SESSION_NAME]);
    }

    /**
     * @return bool
     */
    public static function isExist(): bool
    {
        session_start();

        return isset($_SERVER[self::TOKEN_SESSION_NAME]) && !empty($_SERVER[self::TOKEN_SESSION_NAME]);
    }
}
