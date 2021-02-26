<?php


namespace OpenProvider\API;


class APIManager
{
    /**
     * @var array|mixed
     */
    private $params;
    /**
     * @var APIV1|API
     */
    private $api;

    public function __construct($params = [])
    {
        $this->params = $params;
    }

    public function __call($methodName, $args)
    {
        if (method_exists(APIV1::class, $methodName)) {
            $this->api = APIFactory::initAPIV1();
        } else {
            $this->api = APIFactory::initAPIXML();
        }

        $this->api->setParams($this->params);
        return call_user_func_array([$this->api, $methodName], $args);
    }

    public function setParams($params)
    {
        $this->params = $params;
    }
}