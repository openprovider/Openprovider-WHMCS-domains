<?php
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
        'hookPoint' => 'InvoicePaid',
        'priority' =>  1,
        'controller' => 'InvoiceController@paid'
    ],
    [
        'hookPoint' => 'AddTransaction',
        'priority' =>  1,
        'controller' => 'InvoiceController@paid'
    ],

];