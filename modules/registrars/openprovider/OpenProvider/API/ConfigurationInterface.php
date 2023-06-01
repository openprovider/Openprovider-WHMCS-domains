<?php

namespace OpenProvider\API;

interface ConfigurationInterface
{
    /**
     * @param string $host
     */
    public function setHost(string $host): void;

    /**
     * @return string
     */
    public function getHost(): string;

    /**
     * @param string $userName
     */
    public function setUserName(string $userName): void;

    /**
     * @return string
     */
    public function getUserName(): string;

    /**
     * @param string $password
     */
    public function setPassword(string $password): void;

    /**
     * @return string
     */
    public function getPassword(): string;

    /**
     * @param bool $debug
     */
    public function setDebug(bool $debug): void;

    /**
     * @return bool
     */
    public function getDebug(): bool;

    /**
     * @param string $token
     */
    public function setToken(string $token): void;

    /**
     * @return string
     */
    public function getToken(): string;
}
