<?php

namespace OpenProvider\WhmcsRegistrar\Controllers\Hooks;

use WeDevelopCoffee\wPower\Models\Domain;
use WHMCS\View\Menu\Item as MenuItem;

/**
 * Class NavigationController
 * OpenProvider Registrar module
 *
 * @copyright Copyright (c) Openprovider 2018
 */

class NavigationController{

    /**
     * @var array Action urls where this hook applies to.
     */
    protected $actionList = [
        'domaindetails',
        'domainregisterns',
        'domaingetepp',
        'domaincontacts',
    ];

    /**
     * @var array Tlds where this applies to.
     */
    protected $tlds = [
        'nl',
        'be',
        'eu'
    ];
    /**
    * 
    * 
    * @return 
    */
    public function hideRegistrarLock (MenuItem $primarySidebar)
    {
        // Only run on domaindetails.
        if(!in_array($_REQUEST['action'], $this->actionList))
            return;

        if(isset($_REQUEST['domainid']) || isset($_REQUEST['id']))
        {
            if(isset($_REQUEST['domainid']))
                $domainId = $_REQUEST['domainid'];
            else
                $domainId = $_REQUEST['id'];

            $domain = Domain::find($domainId);
            $tld= explode('.', $domain->domain, 2)[1];
            if(in_array($tld, $this->tlds))
            {
                if ($primarySidebar->getChild('Domain Details Management')) {
                    $primarySidebar->getChild('Domain Details Management')->removeChild('Registrar Lock Status');
                }
            }
        }

    }
}
