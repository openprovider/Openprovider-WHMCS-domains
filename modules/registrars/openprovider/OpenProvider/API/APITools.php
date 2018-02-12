<?php
namespace OpenProvider\API;

class APITools
{

    public static function tableExists($tblName)
    {
        $tableInfoQuery =   "SELECT 
                                count((1)) as `ct` 
                            FROM 
                                INFORMATION_SCHEMA.TABLES 
                            WHERE 
                                table_schema ='whmcs' 
                                AND 
                                table_name='$tblName'";
        
        $tableInfoResult = full_query($tableInfoQuery);
        $tableInfoData = mysql_fetch_assoc($tableInfoResult);
        
        return (bool)$tableInfoData['ct'];
    }

    public static function createOpenprovidersTable()
    {
        mysql_query("CREATE TABLE IF NOT EXISTS `OpenProviderHandles` 
                (
                    `domainId`     int(11)     DEFAULT NULL,
                    `ownerHandle`  varchar(45) DEFAULT NULL,
                    `adminHandle`  varchar(45) DEFAULT NULL,
                    `techHandle`  varchar(45) DEFAULT NULL,
                    `billingHandle`  varchar(45) DEFAULT NULL,
                    `resellerHandle`  varchar(45) DEFAULT NULL,
                    UNIQUE KEY (`domainId`)
                ) 
                ENGINE=InnoDB DEFAULT CHARSET=utf8");
        
        mysql_query("CREATE TABLE IF NOT EXISTS `OpenproviderCache` 
                (
                    `id`            int(11)      NOT NULL AUTO_INCREMENT,
                    `name`          varchar(100) NOT NULL,
                    `timestamp`     varchar(19)  NOT NULL,
                    `value`         text         NOT NULL,

                    PRIMARY KEY (`id`)
                )
                ENGINE=InnoDB DEFAULT CHARSET=utf8");
    }
    
    public static function createCustomFields()
    {
        //ownerType
        if(mysql_num_rows(mysql_query("SELECT id FROM tblcustomfields WHERE `fieldname` LIKE 'ownerType|%'")) == 0)
        {
            mysql_query("
            INSERT INTO tblcustomfields 
                (`type`,`relid`,`fieldname`,`fieldtype`,`adminonly`,`required`, `fieldoptions`) 
            VALUES 
                ('client', 0, 'ownerType|Owner Type','dropdown', 'on', 'on', 'Company,Individual')");
        }

        
        //VAT Number
        if(mysql_num_rows(mysql_query("SELECT id FROM tblcustomfields WHERE `fieldname` LIKE 'VATNumber|%'")) == 0)
        {
            mysql_query("
            INSERT INTO tblcustomfields 
                (`type`,`relid`,`fieldname`,`fieldtype`,`adminonly`,`required`) 
            VALUES 
                ('client', 0, 'VATNumber|VAT Number','text', 'on', 'on')");

        } 
        
        //Business registration number
        if(mysql_num_rows(mysql_query("SELECT id FROM tblcustomfields WHERE `fieldname` LIKE 'companyRegistrationNumber|%'")) == 0)
        {
            mysql_query("
            INSERT INTO tblcustomfields 
                (`type`,`relid`,`fieldname`,`fieldtype`,`adminonly`,`required`) 
            VALUES 
                ('client', 0, 'companyRegistrationNumber|Business registration number','text', 'on', 'on')");
        } 
        
        //Personal ID number
        if(mysql_num_rows(mysql_query("SELECT id FROM tblcustomfields WHERE `fieldname` LIKE 'socialSecurityNumber|%'")) == 0)
        {
            mysql_query("
            INSERT INTO tblcustomfields 
                (`type`,`relid`,`fieldname`,`fieldtype`,`adminonly`,`required`) 
            VALUES 
                ('client', 0, 'socialSecurityNumber|Personal ID number','text', 'on', 'on')");
        } 
        
        //Passport number
        if(mysql_num_rows(mysql_query("SELECT id FROM tblcustomfields WHERE `fieldname` LIKE 'passportNumber|%'")) == 0)
        {
            mysql_query("
            INSERT INTO tblcustomfields 
                (`type`,`relid`,`fieldname`,`fieldtype`,`adminonly`,`required`) 
            VALUES 
                ('client', 0, 'passportNumber|Passport number','text', 'on', '')");
        } 
    }

    
    public static function getClientCustomFields($customfields)
    {
        $fields = array();
        $q      =  mysql_query("SELECT `id`,`fieldname`,`fieldtype`,`fieldoptions` FROM tblcustomfields WHERE `type`='client' AND `relid`=0");
   
        while($r = mysql_fetch_assoc($q))
        {
            foreach($customfields as $customfield)
            {
                if($customfield['id'] == $r['id'])
                {
                    if(strpos($r['fieldname'], '|') > -1 )
                    {
                        $name = explode('|', $r['fieldname']);
                        $name = $name[0];
                    } else
                    {
                        $name = $r['fieldname'];
                    }
                    
                    $fields[$name] = $customfield['value'];
                }
            }
        }
        
        return $fields;
    }
    
    
    
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
            $nsIp = empty($nsParts[1]) ? null : trim($nsParts[1]);

            if (empty($nsIp))
            {
                $api = new \OpenProvider\API\API($params);
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

    public static function createCustomerHandle($params, \OpenProvider\API\Customer $customer, $search = false)
    {
        if($search)
        {
            $api    =   new \OpenProvider\API\API($params);
            // checking if the customer exits
            $result =   $api->searchCustomerInOPdatabase($customer);

            if ($result['total'] > 0)
            {
                return $result['results'][0]['handle'];
            }
        }
        
        // there is no such customer, creates an entry for a new one
        $api    =    new \OpenProvider\API\API($params);                            // IMPORTANT: have to create API object once again
        $result =   $api->createCustomerInOPdatabase($customer);
        return $result['handle'];
    }
    
    public static function readCustomerHandles($userId)
    {
        $searchResult = select_query('tbldomains', 'id', array(
            'registrar' => 'openprovider',
            'userid'    => $userId,
        ));
        
        $searchData = mysql_fetch_assoc($searchResult);
        
        if (isset($searchData['id']))
        {
            $egDomainId = $searchData['id'];

            return new \OpenProvider\API\Handles($egDomainId);
        }
        else
        {
            return null;
        }
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

    public static function convertXmlToPhpObj($node)
    {
        $ret = array();

        if (is_object($node) && $node->hasChildNodes())
        {
            foreach ($node->childNodes as $child)
            {
                $name = mb_convert_encoding($child->nodeName, \OpenProvider\API\APIConfig::$encoding);
                if ($child->nodeType == XML_TEXT_NODE)
                {
                    $ret = mb_convert_encoding($child->nodeValue, \OpenProvider\API\APIConfig::$encoding);
                }
                else
                {
                    if ('array' === $name)
                    {
                        return self::parseArray($child);
                    }
                    else
                    {
                        $ret[$name] = self::convertXmlToPhpObj($child);
                    }
                }
            }
        }
        return 0 < count($ret) ? $ret : null;
    }
    
    // parse array
    protected static function parseArray ($node)
    {
            $ret = array();
            foreach ($node->childNodes as $child) {
                    $name = mb_convert_encoding($child->nodeName, \OpenProvider\API\APIConfig::$encoding);
                    if ('item' !== $name) {
                            throw new \Exception('Wrong message format');
                    }
                    $ret[] = self::convertXmlToPhpObj($child);
            }
            return $ret;
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
    
    public static function getHandlesForDomainId($domainId) 
    {
        $q = mysql_query("SELECT * FROM OpenProviderHandles WHERE `domainId` = $domainId");
        $result = mysql_fetch_assoc($q);
        
        return $result;
    }
    
    public static function saveNewHandles($newHandles)
    {
        $query = "INSERT INTO `OpenProviderHandles` "
                . "(`domainId`, `ownerHandle`, `adminHandle`, `techHandle`, `billingHandle`, `resellerHandle`) "
                . "VALUES ('".$newHandles['domainid']."', '".$newHandles['ownerHandle']."', '".$newHandles['adminHandle']."', '".$newHandles['techHandle']."', '".$newHandles['billingHandle']."', '".$newHandles['resellerHandle']."');";
        
        $res = mysql_query($query);
        
        return $res;
    }

}
