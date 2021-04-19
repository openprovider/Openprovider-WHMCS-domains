<?php

namespace OpenProvider\WhmcsRegistrar\cron;

use OpenProvider\WhmcsRegistrar\src\DomainSync;
use OpenProvider\WhmcsHelpers\Activity;

/**
 * OpenProvider Registrar module
 *
 * @copyright Copyright (c) Openprovider 2018
 */

include('BaseCron.php');

// 1. Get all domains for $openprovider who need an update
$core = openprovider_registrar_core();
$core->launch();
$launcher = openprovider_bind_required_classes($core->launcher);
$apiHelper = $launcher->get(\OpenProvider\API\ApiHelper::class);
$idn = $launcher->get(\idna_convert::class);
$DomainSync = new DomainSync($apiHelper, $idn);

// 2. Do we have anything?
if(!$DomainSync->has_domains_to_process())
{
    // Trigger a send in case an user wants to receive empty reports.
    Activity::send_email_report();

    // Send a 200 HTTP code back. Some mod_php setups require this. Otherwise, a HTTP 500 is returned.
    header("HTTP/1.1 200 OK");
    exit();
}

// 3. Loop through every domain
$DomainSync->process_domains();

// 4. Send activity reports
Activity::send_email_report();

// Send a 200 HTTP code back. Some mod_php setups require this. Otherwise, a HTTP 500 is returned.
header("HTTP/1.1 200 OK");
exit();
