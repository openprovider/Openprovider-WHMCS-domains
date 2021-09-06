# Openprovider Domain Sync - DEPRECATED

This feature is deprecated since version 5.3 of the Openprovider domain module, and is not recommended for versions WHMCS 8+. We suggest that you use the WHMCS native domain sync and do not use the Openprovider custom sync for  WHMCS 8 and higher.

## Setting up cron task

In order to run the Openprovider cron sync task:

- a cron job needs to be scheduled for the script `<WHMCS directory>/modules/registrars/openprovider/cron/DomainSync.php`.
- The parameter "syncUseNativeWHMCS" needs to be set to "false" in the file `/modules/registrars/openprovider/configuration/advanced-module-configurations.php`
- [Additional parameters can also be configured](advanced_configurations.md)

This script keeps domain statuses, expiration dates, WPP, and auto renew settings synchronized between Openprovider and WHMCS. Domain statuses and expiration dates are led by domain settings in Openprovider, and auto renew settings are led by WHMCS settings. This script updates 200 domains per execution by default (the value can be changed in the module configuration), and should be run so that all domains are synchronized within 6 hours. For example, if you have 7000 domains in WHMCS with Openprovider, the cron job needs to be run `7000 / 200 = 35` times every 6 hours, i.e. at least once every 10 minutes. (360 minutes / 35 syncs = at most 10.2 minutes/sync)

_**NB!**_ WHMCS has its own synchronization script, `<WHMCS directory>/crons/cron.php`, which performs various maintenance activities, including invoice generation, domain statuses sync with other registrars, etc. 

Since WHMCS' implementation of domain status synchronization used to be lacking in some aspects, DomainSync.php script was provided by Openprovider as an addition to the WHMCS' utility . It needs to be scheduled as a separate cron task.

## Domain Sync Email Update

The DomainSync task from the Openprovider domain module sends an email report every time a domain object in WHMCS or Openprovider is modified. If "Send empty activity reports is selected" in the module configuration window, then the update will be sent every time the task runs, even if no domains have been modified.

To configure, navigate to **Setup > Staff Management > Administrator Roles** and select the group of administrators who you want to be receiving email reports. Ensure that **System emails** is selected in this group:

![alt text](http://pic001.filehostserver.eu/116672.png "Domain Sync Email update")

## Updated settings

DomainSync summarizes the following changes to the WHMCS domain objects:

### Openprovider is leading, WHMCS settings are updated:

###### 1. Expiration date

1. This sets WHMCS expiration date equal to Openprovider expiration date. Note that for certain TLDs there is an offset between the Openprovider expiration date and the Registry expiration date, but the Openprovider expiration date is the date for which a payment is due for renewals.

###### 2. Due date

1. This is the date WHMCS invoices are due on, and it's equal to WHMCS expiration date offset by the value selected in **Next due date offset** on the module's configuration page (if **Synchronize due date with offset?** is checked). 

For example, if the expiration date is set tp _May 20_ and the offset is set to 10 days, then the Next due date will be set to _May 10_.

2. If **Synchronize due date with offset?** is not checked, then due date will be set equal to expiration date.

###### 3. Domain status

Domains statuses are mapped according to the following pattern:

| Openprovider | WHMCS   | Description                                                  |
| ------------ | ------- | ------------------------------------------------------------ |
| ACT          | Active  | The domain name is active                                    |
| DEL          | Expired | The domain name has been deleted, but may still be restored  |
| PEN          | Pending | The domain name request is pending further information before the process can continue |
| REQ          | Pending | The domain name request has been placed, but not yet finished |
| RRQ          | Pending | The domain name restore has been requested, but not yet completed |
| SCH          | Pending | The domain name is scheduled for transfer in the future      |

_**NB!**_ If a domain has the status FAI in Openprovider, because of a transfer failure, then an error will be logged in the admin area as " The domain name request has failed "

### WHMCS is leading, Openprovider settings are updated:

Domain Auto-renew and ID protection settings are sent to Openprovider immediately if any changes are made from WHMCS. If something is changed in Openprovider, then the DomainSync task will ensure that auto-renew and ID settings stay synchronized.

###### 1. Domain Auto renew

1. If a domain is set to auto renew **On** in WHMCS, then the corresponding domain object in Openprovider is set to **Global Default**. If WHMCS domain object is set to auto-renew **Off** then the corresponding domain object in Openprovider is also set to **Off**.

###### 2. Domain Whois Privacy Protection (ID protection)

1. ID protection settings of a domain are mapped to Openprovider Whois Privacy Protection settings.

Any changes made to a domain by the DomainSync task will be listed in an email sent to the administrators in the group you have specified:

![alt text](http://pic001.filehostserver.eu/116671.png "Domain Sync")