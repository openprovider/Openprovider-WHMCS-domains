<?php


namespace OpenProvider\API;


class XmlApiAdapter implements APIInterface
{
    /**
     * @var API
     */
    private $xmlApi;

    public function __construct(API $xmlApi)
    {
        $this->xmlApi = $xmlApi;
    }

    /**
     * @param string $method
     * @param array $args
     * @return array
     * @throws \Exception
     */
    public function call(string $method, array $args = [])
    {
        return $this->xmlApi->sendRequest($method, $args);
    }
}
