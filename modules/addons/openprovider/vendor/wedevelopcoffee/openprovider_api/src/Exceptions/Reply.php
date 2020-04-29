<?php
namespace WeDevelopCoffee\OpenProvider_Api;

use WeDevelopCoffee\OpenProvider_Api\Exceptions\ApiException;

class Reply
{
    protected $faultCode = 0;
    protected $faultString = null;
    protected $value = array();
    protected $warnings = array();
    protected $raw = null;
    protected $dom = null;
    protected $filters = [];
    protected $maintenance = null;
    
    public function __construct ($str = null) {
        if ($str) {
            $this->raw = $str;
            $this->_parseReply($str);
        }
    }
    protected function _parseReply ($str = '')
    {
        $dom = new DOMDocument;
        $result = $dom->loadXML(trim($str));
        if (!$result) {
            error_log("Cannot parse xml: '$str'");
        }
        
        $arr = Api::convertXmlToPhpObj($dom->documentElement);
        if ((!is_array($arr) && trim($arr) == '') ||
        $arr['reply']['code'] == 4005)
        {
            throw new ApiException("API is temporarily unavailable due to maintenance", 4005);
        }
        
        $this->faultCode = (int) $arr['reply']['code'];
        $this->faultString = $arr['reply']['desc'];
        $this->value = $arr['reply']['data'];
        if (isset($arr['reply']['warnings'])) {
            $this->warnings = $arr['reply']['warnings'];
        }
        if (isset($arr['reply']['maintenance'])) {
            $this->maintenance = $arr['reply']['maintenance'];
        }
    }
    public function encode ($str)
    {
        return Api::encode($str);
    }
    public function setFaultCode ($v)
    {
        $this->faultCode = $v;
        return $this;
    }
    public function setFaultString ($v)
    {
        $this->faultString = $v;
        return $this;
    }
    public function setValue ($v)
    {
        $this->value = $v;
        return $this;
    }
    public function getValue ()
    {
        return $this->value;
    }
    public function setWarnings ($v)
    {
        $this->warnings = $v;
        return $this;
    }
    public function getDom ()
    {
        return $this->dom;
    }
    public function getWarnings ()
    {
        return $this->warnings;
    }
    public function getMaintenance ()
    {
        return $this->maintenance;
    }
    public function getFaultString () {
        return $this->faultString;
    }
    public function getFaultCode ()
    {
        return $this->faultCode;
    }
    public function getRaw ()
    {
        if (!$this->raw) {
            $this->raw .= $this->_getReply ();
        }
        return $this->raw;
    }
    public function addFilter($filter)
    {
        $this->filters[] = $filter;
    }
    public function _getReply ()
    {
        $dom = new DOMDocument('1.0', Api::$encoding);
        $rootNode = $dom->appendChild($dom->createElement('openXML'));
        $replyNode = $rootNode->appendChild($dom->createElement('reply'));
        $codeNode = $replyNode->appendChild($dom->createElement('code'));
        $codeNode->appendChild($dom->createTextNode($this->faultCode));
        $descNode = $replyNode->appendChild($dom->createElement('desc'));
        $descNode->appendChild(
            $dom->createTextNode(Api::encode($this->faultString))
        );
        $dataNode = $replyNode->appendChild($dom->createElement('data'));
        Api::convertPhpObjToDom($this->value, $dataNode, $dom);
        if (0 < count($this->warnings)) {
            $warningsNode = $replyNode->appendChild($dom->createElement('warnings'));
            Api::convertPhpObjToDom($this->warnings, $warningsNode, $dom);
        }
        $this->dom = $dom;
        foreach ($this->filters as $f) {
            $f->filter($this);
        }
        return $dom->saveXML();
    }
}