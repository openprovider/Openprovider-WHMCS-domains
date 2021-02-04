<?php
namespace OpenProvider\API;

/**
 * Class ReplyJSON
 * OpenProvider Registrar module
 *
 * @copyright Copyright (c) Openprovider 2021
 */

class ReplyJSON
{

    protected $faultCode = 0;
    protected $faultString = null;
    protected $value = array();
    protected $warnings = array();
    protected $raw = null;

    public function __construct($str = null)
    {
        if ($str)
        {
            $this->raw = $str;
            $this->_parseReply($str);
        }
    }

    protected function _parseReply($str = '')
    {
        $result = json_decode($str);

        if (!$result)
        {
            logModuleCall('openprovider', 'connecting_with_openprovider_error', null, $str, $result, null);
            throw new \Exception('No json reply');
        }

        $this->faultCode = (int) $result->code;
        $this->faultString = $result->desc;
        $this->value = $result->data;

        if (isset($result->warnings))
        {
            $this->warnings = $result->warnings;
        }
    }

    public function getValue()
    {
        return $this->value;
    }

    public function getWarnings()
    {
        return $this->warnings;
    }

    public function getFaultString()
    {
        return $this->faultString;
    }

    public function getFaultCode()
    {
        return $this->faultCode;
    }
}
