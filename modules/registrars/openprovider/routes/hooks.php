<?php
/**
 * OpenProvider Registar module
 *
 * @copyright Copyright (c) Openprovider 2018
 */

/**
 * Hooks
 * ----------------
 * 
 * Instead of mapping routes automagically to controllers, we use
 * a whitelist of routes with the controllers mapped.
 * 
 * If the string only contains a-zA-Z0-9_, the namespace will be 
 * guessed and added. 
 */
return [
    [
        'hookPoint' => 'ClientAreaPageDomainDetails',
        'priority' =>  1,
        'controller' => 'DomainController@saveDomainEdit'
    ],
    [
        'hookPoint' => 'DomainEdit',
        'priority' =>  1,
        'controller' => 'DomainController@saveDomainEdit'
    ],
    [
        'hookPoint' => 'ClientAreaPrimarySidebar',
        'priority' =>  1,
        'controller' => 'NavigationController@hideRegistrarLock'
    ],
    [
        'hookPoint' => 'AdminHomeWidgets',
        'priority' =>  1,
        'controller' => 'AdminWidgetController@showBalance'
    ],
    [
        'hookPoint' => 'ClientAreaPageDomainDNSManagement',
        'priority' => 10,
        'controller' => 'DnsNotificationController@notify'
    ],
    [
        'hookPoint' => 'ClientAreaFooterOutput',
        'priority' => 10,
        'controller' => 'DnsClientJavascriptController@run'
    ],
    [
        'hookPoint' => 'ClientAreaPrimarySidebar',
        'priority'  => 1,
        'controller' => 'ClientAreaPrimarySidebarController@show'
    ],
    [
        'hookPoint' => 'ClientAreaPageDomainDNSManagement',
        'priority'  => 1,
        'controller' => 'DnsAuthController@redirectDnsManagementPage'
    ],
    [
        'hookPoint' => 'AdminClientProfileTabFields',
        'priority'  => 1,
        'controller'=> 'AdminClientProfileTabController@additionalFields',
    ],
    [
        'hookPoint' => 'AdminClientProfileTabFieldsSave',
        'priority'  => 1,
        'controller'=> 'AdminClientProfileTabController@saveFields',
    ],
];

