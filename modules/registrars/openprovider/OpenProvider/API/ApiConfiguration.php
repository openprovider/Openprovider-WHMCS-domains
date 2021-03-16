<?php

namespace OpenProvider\API;

class ApiConfiguration implements ConfigurationInterface
{
    /**
     * @var string
     */
    private string $host;
    /**
     * @var string
     */
    private string $userName;
    /**
     * @var string
     */
    private string $password;
    /**
     * @var bool
     */
    private bool $debug;
    /**
     * @var string
     */
    private string $token;

    /**
     * @return string
     */
    public function getHost(): string
    {
        return $this->host ?? '';
    }

    /**
     * @param string $host
     */
    public function setHost(string $host): void
    {
        $this->host = $host;
    }

    /**
     * @return string
     */
    public function getUserName(): string
    {
        return $this->userName ?? '';
    }

    /**
     * @param string $userName
     */
    public function setUserName(string $userName): void
    {
        $this->userName = $userName;
    }

    /**
     * @return string
     */
    public function getPassword(): string
    {
        return $this->password ?? '';
    }

    /**
     * @param string $password
     */
    public function setPassword(string $password): void
    {
        $this->password = $password;
    }

    /**
     * @return bool
     */
    public function getDebug(): bool
    {
        return $this->debug;
    }

    /**
     * @param bool $debug
     */
    public function setDebug(bool $debug): void
    {
        $this->debug = $debug;
    }

    /**
     * @return string
     */
    public function getToken(): string
    {
        return $this->token ?? '';
    }

    /**
     * @param string $token
     * @return void
     */
    public function setToken(string $token): void
    {
        $this->token = $token;
    }
}
