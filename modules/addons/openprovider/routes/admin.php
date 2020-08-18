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

    //
    'index' => 'SupportController@index',

    // Support
    'supportDownload' => 'SupportController@download',
    'supportIndex' => 'SupportController@index',

    // Domain transfers
    'scheduledDomainTransfers' => 'ScheduledDomainTransferController@index',
    'cleanScheduledDomainTransfers' => 'ScheduledDomainTransferController@clean',
    'toggleFilterScheduledDomainTransfers' => 'ScheduledDomainTransferController@toggle',

];