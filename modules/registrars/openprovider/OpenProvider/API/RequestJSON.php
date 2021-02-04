<?php

namespace OpenProvider\API;

/**
 * Class RequestJSON
 * OpenProvider Registrar module
 *
 * @copyright Copyright (c) Openprovider 2021
 */
class RequestJSON
{

    protected $endpoint;

    protected $completedUrl;

    protected $args;

    protected $header;

    protected $client;

    protected $method;


    public function __construct()
    {
        $this->client = \OpenProvider\API\APIConfig::$moduleVersion;
    }

    /**
     * @param array $args
     * @return $this
     */
    public function setArgs(array $args): RequestJSON
    {
        $this->args = $args;
        return $this;
    }

    /**
     * @return array
     */
    public function getArgs(): array
    {
        return $this->args;
    }

    /**
     * @return string
     */
    public function getArgsJson(): string
    {
        return json_encode($this->args);
    }

    /**
     * @return mixed
     */
    public function getEndpoint()
    {
        return $this->endpoint;
    }

    /**
     * @param string $endpoint
     * @return $this
     */
    public function setEndpoint(string $endpoint): RequestJSON
    {
        $this->endpoint = $endpoint;
        return $this;
    }

    /**
     * @return string
     */
    public function getMethod(): string
    {
        return $this->method;
    }

    /**
     * @param string $method
     * @return $this
     */
    public function setMethod(string $method): RequestJSON
    {
        $this->method = $method;

        return $this;
    }

    /**
     * @param string $baseUrl
     * @param array $substitutionArgs
     * @return $this
     */
    public function processUrl(
        string $baseUrl,
        array $substitutionArgs = []
    ): RequestJSON
    {
        $this->completedUrl = $baseUrl . $this->endpoint;

        if (empty($substitutionArgs))
            return $this;

        foreach ($substitutionArgs as $key => $value) {
            $template           = "/{{$key}}/i";
            $this->completedUrl = preg_replace($template, $value, $this->completedUrl);
        }

        return $this;
    }

    /**
     * @return string
     */
    public function getUrl(): string
    {
        return $this->completedUrl;
    }
}
