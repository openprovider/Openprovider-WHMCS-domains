<?php


namespace OpenProvider\API;

class XmlApiAdapter implements ApiInterface
{
    /**
     * @var API
     */
    private $xmlApi;

    /**
     * XmlApiAdapter constructor.
     * @param API $xmlApi
     */
    public function __construct(API $xmlApi)
    {
        $this->xmlApi = $xmlApi;
    }

    /**
     * @param string $cmo
     * @param array $args
     * @return array
     * @throws \Exception
     */
    public function call(string $cmo, array $args = [])
    {
        return $this->xmlApi->sendRequest($cmo, $args);
    }
}
