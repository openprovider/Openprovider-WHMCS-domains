<?php

namespace OpenProvider\API;

class Handles
{
    public $ownerHandle;
    public $adminHandle;
    public $techHandle;
    public $billingHandle;
    public $resellerHandle;
    
    public function __construct($search = null)
    {
        if ($search)
        {
            if ($search instanceof \OpenProvider\API\Domain)
            {
                return $this->getByDomain($search);
            }
            else
            {
                $this->getById($search);
            }
        }
    }
    
    public function getByDomain(\OpenProvider\API\Domain $domain)
    {
        // get domain id
        $selectResult = select_query('tbldomains', 'id', array(
            'registrar' => 'openprovider',
            'domain' => $domain->getFullName(),
        ));
        if (!mysql_num_rows($selectResult))
        {
            return;
        }
        
        $selectData = mysql_fetch_assoc($selectResult);
        
        $domainId = $selectData['id'];
        return $this->getById($domainId);
    }
    
    public function getById($domainId)
    {
        $result = select_query('OpenProviderHandles', '*', array(
            'domainId' => $domainId,
        ));
        
        $data = mysql_fetch_assoc($result);
        
        return $data;
    }
    
    /**
     * Import domains handlers into WHMCS
     * @param \OpenProvider\API\API $api
     * @param \OpenProvider\API\Domain $domain
     * @param type $domainId
     * @param type $useLocalHandle
     */
    public function importToWHMCS(\OpenProvider\API\API $api, \OpenProvider\API\Domain $domain, $domainId, $useLocalHandle)
    {
        $domainInfo = $api->retrieveDomainRequest($domain);
        
        mysql_query("REPLACE INTO OpenProviderHandles VALUES('".$domainId."', '".$domainInfo['ownerHandle']."', '".$domainInfo['adminHandle']."', '".$domainInfo['techHandle']."', '".$domainInfo['billingHandle']."', '".$domainInfo['resellerHandle']."')");   
        
        if ($useLocalHandle)
        {
            $this->updateInWHMCS($api, $domain, $domainId, $useLocalHandle, $domainInfo);
        }
    }
    
    /**
     * Update domain handlers database
     * @param \OpenProvider\API\API $api
     * @param \OpenProvider\API\Domain $domain
     * @param type $domainId
     * @param type $useLocalHandle
     * @param type $domainInfo
     */
    public function updateInWHMCS(\OpenProvider\API\API $api, \OpenProvider\API\Domain $domain, $domainId, $useLocalHandle, $domainInfo = null)
    {
        if (!$domainInfo) {
            $domainInfo = $api->retrieveDomainRequest($domain);
        }
        
        if ($useLocalHandle) {
            // get userid
            $userIdResult = select_query('tbldomains', 'userid', array(
                'domain' => $domain->getFullName(),
            ));
            $userIdData = mysql_fetch_assoc($userIdResult);
            $userId = $userIdData['userid'];
            
            // get all domains
            $domainsQuery = "SELECT `id` "
                            . "FROM OpenProviderHandles "
                            . "WHERE "
                            . "OpenProviderHandles.domainId IN "
                                . "(SELECT `tbldomains`.`id` "
                                . "FROM `tbldomains` "
                                . "WHERE `tbldomains`.`userid` = $userId)";
            
            $domainsResult = full_query($domainsQuery);
            while ($domainsData = mysql_fetch_array($domainsResult)) {
                mysql_query("REPLACE INTO OpenProviderHandles VALUES('".$domainId."', '".$domainInfo['ownerHandle']."', '".$domainInfo['adminHandle']."', '".$domainInfo['techHandle']."', '".$domainInfo['billingHandle']."', '".$domainInfo['resellerHandle']."')");   

            }
        } else { // update handle only for this domain
            mysql_query("REPLACE INTO OpenProviderHandles VALUES('".$domainId."', '".$domainInfo['ownerHandle']."', '".$domainInfo['adminHandle']."', '".$domainInfo['techHandle']."', '".$domainInfo['billingHandle']."', '".$domainInfo['resellerHandle']."')");   

        }
        
    }
}
