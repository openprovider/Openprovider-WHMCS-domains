<?php
namespace OpenProvider\API;

/**
 * Class APITools
 * OpenProvider Registrar module
 *
 * @copyright Copyright (c) Openprovider 2018
 */

class APITools
{   
    public static function createNameserversArray($params)
    {
        $nameServers = array();

        //can be used to hard-code a nameserver overwrite when using the DNS management addon 
        // if($params['dnsmanagement'] == true)
        // {
        //     $params['ns1'] = 'ns1.openprovider.nl';
        //     $params['ns2'] = 'ns2.openprovider.be';
        //     $params['ns3'] = 'ns3.openprovider.eu';
        //     $params['ns4'] = null;
        //     $params['ns5'] = null;
        // }


        for ($i = 1; $i <=5; $i++)
        {
            $ns = $params["ns{$i}"];
            if (!$ns)
            {
                continue;
            }
            
            $nsParts = explode('/', $ns);
            $nsName = trim($nsParts[0]);
            // $nsIp = empty($nsParts[1]) ? null : trim($nsParts[1]);

            $nsIp = empty($nsParts[1]) ? gethostbyname($nsName) : trim($nsParts[1]);

            if (empty($nsIp))
            {
                $api = new \OpenProvider\API\API();
                $api->setParams($params);
                $searchRasult = $api->sendRequest('searchNsRequest', array(
                    'pattern' => $nsName,
                ));
                
                if ($searchRasult['total'] > 0)
                {
                    $nsIp = $searchRasult['results'][0]['ip'];
                }
            }

            $nameServers[] = new \OpenProvider\API\DomainNameServer(array(
                'name'  =>  $nsName,
                'ip'    =>  $nsIp
            ));
        }
        
        if (count($nameServers) < 2)
        {
            throw new \Exception('You must enter minimum 2 nameservers');
        }

        return $nameServers;
    }
    
    /*
     * converts php-structure to DOM-object.
     *
     * @param array $arr php-structure
     * @param SimpleXMLElement $node parent node where new element to attach
     * @param DOMDocument $dom DOMDocument object
     * @return SimpleXMLElement
     */
    public static function convertPhpObjToDom($arr, $node, $dom)
    {
        //Convert to array
        if(is_object($arr))
        {
            $arr    =   json_decode(json_encode($arr), true);
        }
        
        if (is_array($arr))
        {
            /**
             * If arr has integer keys, this php-array must be converted in
             * xml-array representation (<array><item>..</item>..</array>)
             */
            $arrayParam = array();
            foreach ($arr as $k => $v)
            {
                if (is_integer($k))
                {
                    $arrayParam[] = $v;
                }
            }
            if (0 < count($arrayParam))
            {
                $node->appendChild($arrayDom = $dom->createElement("array"));
                foreach ($arrayParam as $key => $val)
                {
                    $new = $arrayDom->appendChild($dom->createElement('item'));
                    self::convertPhpObjToDom($val, $new, $dom);
                }
            }
            else
            {
                foreach ($arr as $key => $val)
                {
                    $new = $node->appendChild($dom->createElement(mb_convert_encoding($key, \OpenProvider\API\APIConfig::$encoding)));
                    self::convertPhpObjToDom($val, $new, $dom);
                }
            }
        }
        else
        {
            $node->appendChild($dom->createTextNode(mb_convert_encoding($arr, \OpenProvider\API\APIConfig::$encoding)));
        }
    }

    public static function convertXmlToPhpArray($xml)
    {
        $simplexml = simplexml_load_string($xml);

        $array = self::convertObjToArray($simplexml);

        return $array;
    }

    public static function convertObjToArray($obj)
    {
        if(!is_object($obj))
            return false;

        $returnArray = [];

        foreach($obj as $key => $value)
        {
            $key = mb_convert_encoding($key, \OpenProvider\API\APIConfig::$encoding);

            if($key == 'array')
                return self::convertObjToArray($value);

            // Check if we have children.
            if(count($value) != 0)
            {
                $array = self::convertObjToArray($value);
                $value = $array;
            }
            else
            {
                $value = mb_convert_encoding((string) $value, \OpenProvider\API\APIConfig::$encoding);
            }

            if($key == 'item')
            {
                $returnArray[] = $value;
            }
            else
            {
                $returnArray[$key] = $value;
            }

        }

        return $returnArray;
    }

    public static function checkIfNsIsDefault(array $nameservers) {

        $return = true;

        if (
            $nameservers[0]->name != 'ns1.openprovider.nl' ||
            $nameservers[1]->name != 'ns2.openprovider.be' ||
            $nameservers[2]->name != 'ns3.openprovider.eu'                    
            ) {
            $return = false;
        }

        return $return;            
    }

    public static function getEncodedDomainName($domainname)
    {
        $tempArr = explode('.', $domainname);

        if (count($tempArr) <= 1) {
            return false;
        }

        $count = count($tempArr) - 1;
        unset($tempArr[$count]);
        $name = implode('.', $tempArr);

        require_once __DIR__ . '/../../classes/idna_convert.class.php';
        $idnConvert = new \idna_convert();
        $return = $idnConvert->encode($name);

        return $return;                
    }

}
