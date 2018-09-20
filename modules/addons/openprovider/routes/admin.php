<?php
/**
 * ADMIN ROUTES
 * ----------------
 * 
 * Instead of mapping routes automagically to controllers, we use
 * a whitelist of routes with the controllers mapped.
 * 
 * If the string only contains a-zA-Z0-9_, the namespace will be 
 * guessed and added. 
 */
return [

    // Import pricelist
    'index' => 'domainPriceListController@index',
    'synchroniseTLD' => 'domainPriceListController@synchronise',

    // Uninvoiced domains
    'index' => 'UninvoicedDomainController@index',
    'uninvoicedDomainsIndex' => 'UninvoicedDomainController@index',
    'problemDetails' => 'UninvoicedDomainController@problemDetails',
    'createInvoice' => 'UninvoicedDomainController@createInvoiceForm',
    'createInvoicePost' => 'UninvoicedDomainController@createInvoice',
    'ignoreDomain' => 'UninvoicedDomainController@ignoreDomain',

];