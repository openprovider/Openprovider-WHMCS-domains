<?php
namespace OpenProvider\API;

class Reply
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
        $dom = new \DOMDocument;
        $result = $dom->loadXML($str);

        if (!$result)
        {
            logModuleCall('openprovider', 'connecting_with_openprovider_error', null, $str, $result, null);
            throw new \Exception('Cannot parse XML');
        }
        $arr = \OpenProvider\API\APITools::convertXmlToPhpObj($dom->documentElement);
        $this->faultCode = (int) $arr['reply']['code'];
        $this->faultString = $arr['reply']['desc'];
        $this->value = $arr['reply']['data'];
        if (isset($arr['reply']['warnings']))
        {
            $this->warnings = $arr['reply']['warnings'];
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

    public function getRaw()
    {
        $dom = new \DOMDocument('1.0', OP_API::$encoding);
        $rootNode = $dom->appendChild($dom->createElement('openXML'));
        $replyNode = $rootNode->appendChild($dom->createElement('reply'));
        $codeNode = $replyNode->appendChild($dom->createElement('code'));
        $codeNode->appendChild($dom->createTextNode($this->faultCode));
        $descNode = $replyNode->appendChild($dom->createElement('desc'));
        $descNode->appendChild(
                $dom->createTextNode(mb_convert_encoding($this->faultString, \OpenProvider\API\APIConfig::$encoding))
        );
        $dataNode = $replyNode->appendChild($dom->createElement('data'));
        \OpenProvider\API\APITools::convertPhpObjToDom($this->value, $dataNode, $dom);
        if (0 < count($this->warnings))
        {
            $warningsNode = $replyNode->appendChild($dom->createElement('warnings'));
            \OpenProvider\API\APITools::convertPhpObjToDom($this->warnings, $warningsNode, $dom);
        }
        return $dom->saveXML();
    }

}
