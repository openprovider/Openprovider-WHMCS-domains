<?php
use OpenProvider\DomainSync;

// Init WHMCS
require __DIR__ . '/../../../../init.php';
require_once __DIR__ . '/../openprovider.php';


$domain_processing_limit = 200;

if((!isset($_SESSION['adminid']) && $_SESSION['adminid'] == false) && php_sapi_name() != 'cli')
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
