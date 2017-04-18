<?php
use OpenProvider\DomainSync;

// Init WHMCS
require __DIR__ . '/../../../../init.php';
require_once __DIR__ . '/../openprovider.php';


$domain_processing_limit = 200;

if((!isset($_SESSION['adminid']) && $_SESSION['adminid'] == false) && !is_cli())
{
    exit('ACCESS DENIED. CONFIGURE CLI CRON.');
}

// 1. Get all domains for $openprovider who need an update
$DomainSync = new DomainSync($domain_processing_limit);

// 2. Do we have anything?
if(!$DomainSync->has_domains_to_proccess())
	return;

// 3. Loop through every domain
$DomainSync->process_domains();

/**
 * Check if we are running command line.
 *
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