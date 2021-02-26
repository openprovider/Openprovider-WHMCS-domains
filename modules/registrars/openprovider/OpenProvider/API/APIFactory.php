<?php
namespace OpenProvider\API;

class APIFactory
{

    public static function initAPIV1()
    {
        return new APIV1();
    }

    public static function initAPIXML()
    {
        return new API();
    }
}
