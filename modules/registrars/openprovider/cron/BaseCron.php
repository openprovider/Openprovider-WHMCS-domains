<?php

// Init WHMCS
require __DIR__ . '/../../../../init.php';
require_once __DIR__ . '/../openprovider.php';

if((!isset($_SESSION['adminid']) && $_SESSION['adminid'] == false) && !is_cli())
{
    exit('ACCESS DENIED. CONFIGURE CLI CRON.');
}

// Process arguments
if(isset($argv[1]) == '--debug')
    define('OP_REG_DEBUG', true);

/**
 * Check if we are running command line.
 * @copyright WeDevelop.coffee
 * @return bool
 */
function is_cli()
{
    if ( defined('STDIN') )
    {
        return true;
    }

    if ( php_sapi_name() === 'cli' )
    {
        return true;
    }

    if ( array_key_exists('SHELL', $_ENV) ) {
        return true;
    }

    if ( empty($_SERVER['REMOTE_ADDR']) and !isset($_SERVER['HTTP_USER_AGENT']) and count($_SERVER['argv']) > 0)
    {
        return true;
    }

    if ( !array_key_exists('REQUEST_METHOD', $_SERVER) )
    {
        return true;
    }

    return false;
}
