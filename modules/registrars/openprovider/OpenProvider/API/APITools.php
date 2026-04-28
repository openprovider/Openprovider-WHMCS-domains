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
    /**
     * Build a list of DomainNameServer objects from the WHMCS-supplied
     * ns1..ns5 params for register / transfer / save flows.
     *
     * Nameserver IPs are only required for *in-bailiwick* glue records
     * (NS that are the apex domain itself or a subdomain of the domain
     * being registered/transferred). For all other (out-of-bailiwick)
     * nameservers, Openprovider does not require an IP and the module
     * must not gate inclusion on DNS resolution — doing so silently
     * dropped customer-supplied external NS, and the registry then
     * substituted reseller defaults at the moment of transfer, causing
     * silent DNS outages (see issue #525).
     *
     * @param array $params      WHMCS module params; must contain sld, tld,
     *                           and ns1..ns5 (each ns may be in "name" or
     *                           "name/ip" form).
     * @param mixed $apiHelper   Optional ApiHelper for resolving
     *                           glue-record IPs from the registry.
     * @return DomainNameServer[]
     * @throws \Exception When fewer than 2 nameservers are supplied, or a
     *                    glue record lacks an IP that cannot be resolved.
     */
    public static function createNameserversArray($params, $apiHelper = null)
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

        $domainFqdn = strtolower(
            ($params['sld'] ?? '') . (isset($params['tld']) ? '.' . $params['tld'] : '')
        );
        $domainFqdn = trim($domainFqdn, '.');

        for ($i = 1; $i <= 5; $i++) {
            $ns = $params["ns{$i}"] ?? null;
            if (!$ns) {
                continue;
            }

            $nsParts = explode('/', $ns);
            $nsName  = strtolower(trim($nsParts[0]));
            $nsIp    = empty($nsParts[1]) ? null : trim($nsParts[1]);

            if ($nsName === '') {
                continue;
            }

            if (self::isGlueRecord($nsName, $domainFqdn) && empty($nsIp)) {
                // In-bailiwick NS — IP is mandatory at the registry. Try
                // the Openprovider registry first, then DNS resolution as
                // a fallback. Throw if neither produces a valid IPv4 so the
                // user sees the problem instead of silent reseller defaults.
                $nsIp = self::resolveGlueIp($nsName, $params, $apiHelper);

                if (empty($nsIp)) {
                    throw new \Exception(
                        "Nameserver {$nsName} is in-bailiwick (a glue record) "
                        . "but no IP could be resolved. Please supply it "
                        . "explicitly in the form 'name/ip'."
                    );
                }
            }

            $attrs = ['name' => $nsName];
            if (!empty($nsIp)) {
                $attrs['ip'] = $nsIp;
            }

            $nameServers[] = new \OpenProvider\API\DomainNameServer($attrs);
        }

        if (count($nameServers) < 2) {
            throw new \Exception('You must enter minimum 2 nameservers');
        }

        return $nameServers;
    }

    /**
     * A nameserver is a glue record if it is the apex domain itself or a
     * subdomain of the domain being registered/transferred.
     */
    private static function isGlueRecord(string $nsName, string $domainFqdn): bool
    {
        if ($domainFqdn === '' || $nsName === '') {
            return false;
        }

        return $nsName === $domainFqdn
            || str_ends_with($nsName, '.' . $domainFqdn);
    }

    /**
     * Resolve a glue-record IPv4. First tries the Openprovider registry
     * (REST via $apiHelper, otherwise the legacy XML API), then falls back
     * to gethostbyname(). Returns null when no valid IPv4 could be found.
     */
    private static function resolveGlueIp(string $nsName, array $params, $apiHelper = null): ?string
    {
        // Prefer the REST helper when available.
        if ($apiHelper !== null && method_exists($apiHelper, 'getNameserverList')) {
            try {
                $list = $apiHelper->getNameserverList($nsName);
                foreach ((array) $list as $entry) {
                    if (isset($entry->name) && $entry->name === $nsName && !empty($entry->ip)) {
                        return $entry->ip;
                    }
                }
            } catch (\Throwable $e) {
                // Fall through.
            }
        }

        // Legacy XML API path (still used by some flows).
        try {
            $api = new \OpenProvider\API\API();
            $api->setParams($params);
            $searchResult = $api->sendRequest('searchNsRequest', [
                'pattern' => $nsName,
            ]);
            if (!empty($searchResult['total']) && !empty($searchResult['results'][0]['ip'])) {
                return $searchResult['results'][0]['ip'];
            }
        } catch (\Throwable $e) {
            // Fall through.
        }

        // DNS resolution fallback.
        $resolved = @gethostbyname($nsName);
        if ($resolved !== false
            && $resolved !== $nsName
            && filter_var($resolved, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)
        ) {
            return $resolved;
        }

        return null;
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
        if (is_object($arr)) {
            $arr    =   json_decode(json_encode($arr), true);
        }

        if (is_array($arr)) {
            /**
             * If arr has integer keys, this php-array must be converted in
             * xml-array representation (<array><item>..</item>..</array>)
             */
            $arrayParam = array();
            foreach ($arr as $k => $v) {
                if (is_integer($k)) {
                    $arrayParam[] = $v;
                }
            }
            if (0 < count($arrayParam)) {
                $node->appendChild($arrayDom = $dom->createElement("array"));
                foreach ($arrayParam as $key => $val) {
                    $new = $arrayDom->appendChild($dom->createElement('item'));
                    self::convertPhpObjToDom($val, $new, $dom);
                }
            } else {
                foreach ($arr as $key => $val) {
                    $new = $node->appendChild($dom->createElement(mb_convert_encoding($key, \OpenProvider\API\APIConfig::$encoding)));
                    self::convertPhpObjToDom($val, $new, $dom);
                }
            }
        } else {
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
        if (!is_object($obj))
            return false;

        $returnArray = [];

        foreach ($obj as $key => $value) {
            $key = mb_convert_encoding($key, \OpenProvider\API\APIConfig::$encoding);

            if ($key == 'array')
                return self::convertObjToArray($value);

            // Check if we have children.
            if (count($value) != 0) {
                $array = self::convertObjToArray($value);
                $value = $array;
            } else {
                $value = mb_convert_encoding((string) $value, \OpenProvider\API\APIConfig::$encoding);
            }

            if ($key == 'item') {
                $returnArray[] = $value;
            } else {
                $returnArray[$key] = $value;
            }
        }

        return $returnArray;
    }

    public static function checkIfNsIsDefault(array $nameservers)
    {

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
