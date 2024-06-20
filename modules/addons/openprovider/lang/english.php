<?php
/**
 * Sample addon module language file.
 * Language: English
 */
$_ADDONLANG['supportDownloadSupportFileHeading'] = 'Download support file';
$_ADDONLANG['supportDownloadSupportFileBody'] = 'The support file contains the following information for the last 14 days:
<ul>
    <li>Activity log</li>
    <li>Module log</li>
</ul>
';

$_ADDONLANG['supportDownloadSupportButton'] = 'Download';
$_ADDONLANG['supportZipExtensionWarning'] = 'The PHP zip extension is not installed. This extension is required to gather all required data. <a href="http://php.net/manual/en/zip.installation.php" target="_blank">Installation guide</a>';
$_ADDONLANG['moduleLogWarning'] = 'The module log is empty. To troubleshoot, please retry the failing operation. This will generate API request and response entries in the log. Please refer to <a href="https://docs.whmcs.com/Troubleshooting_Module_Problems" target="_blank">Troubleshooting Module Problems</a>';


$_ADDONLANG['scheduled_domain_transfers_title'] = 'Scheduled domain transfers';
$_ADDONLANG['clean_completed_scheduled_domain_transfer'] = 'Clean completed scheduled domain transfer';
$_ADDONLANG['deleted_all_scheduled_domain_transfers'] = 'Deleted all completed scheduled domain transfers.';
$_ADDONLANG['scheduled_domain_transfers_show_all_domains'] = 'Show all scheduled domain transfers';
$_ADDONLANG['scheduled_domain_transfers_show_scheduled_only_domains'] = 'Show only scheduled domain transfers';

$_ADDONLANG['no_scheduled_domain_transfers'] = 'There are no domain transfers scheduled.';


$_ADDONLANG['dnssectabname'] = 'DNSSEC Management';
$_ADDONLANG['dnssec']['pagename']  = 'DNSSEC Records';
$_ADDONLANG['dnssec']['tablename'] = 'Manage DNSSEC Records';
$_ADDONLANG['dnssec']['activatednssecbutton']   = 'Activate DNSSEC';
$_ADDONLANG['dnssec']['deactivatednssecbutton'] = 'Deactivate DNSSEC';
$_ADDONLANG['dnssec']['alertdnssecnotactivated'] = 'DNSSEC is not active on this domain.';
$_ADDONLANG['dnssec']['alertdnssecactivated']     = 'DNSSEC is active for this domain. If you deactivate DNSSEC, all existing keys will be deleted from this domain.';
$_ADDONLANG['dnssec']['tableheaderflags']       = 'Flags';
$_ADDONLANG['dnssec']['tableheaderalgorithms']  = 'Algorithm';
$_ADDONLANG['dnssec']['tableheaderpublickeys'] = 'Public key';
$_ADDONLANG['dnssec']['tableheaderactions']     = 'Actions';
$_ADDONLANG['dnssec']['tablebuttonactiondelete'] = 'Delete';
$_ADDONLANG['dnssec']['tablebuttonactionsave']   = 'Save';
$_ADDONLANG['dnssec']['buttonadddnssecrecord'] = 'Add A New DNSSEC Record';
