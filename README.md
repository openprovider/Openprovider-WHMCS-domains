# Domain Module for WHMCS

The Openprovider WHMCS module integrates conveniently with your [Openprovider account](https://rcp.openprovider.eu/registration.php#/registration), allowing you to automate many domain provisioning and management tasks, such as registration, renewal, deletion, and updates to contact details.

The module keeps domain expiration dates and auto renew settings synchronized between your WHMCS installation and Openprovider account, making sure the correct domains get renewed each day.

Additionally, the module allows you to use the Openprovider API to check for domain availability, increasing performance over the default domain availability check. 

Features
- Domain registrations and transfers
- Domain updates
- Whois Privacy Protection
- Whois lookup service
- Extended Synchronization of domain data
- Domain status synchronization reports
- Renew domains upon transfer completion


# Installation
1. Download the module
2. Upload `modules/registrars/openprovider` to `<WHMCS directory>/modules/registrars`
3. [Optional] Upload `modules/addons/openprovider` to `<WHMCS directory>/modules/addons`
4. Navigate to **Setup > Products/Services > Domain Registrars** and activate Openprovider. Use `https://api.openprovider.eu` as the API url. DNS templates are loaded once valid login details are saved. Use the table below as a reference
5. Click **Save**
6. Select the DNS template (if needed)

![alt text](http://pic001.filehostserver.eu/133632.png "Openprovider registrar configuration screen")

7. Click **Save**
8. Navigate to **Setup > Products/Services > Domain Pricing** and select Openprovider as registrar for every TLD
9. Configure the [cron job](https://gitlab.ispnoc.net/-/ide/project/WHMCS/openprovider/blob/op-v3.2/-/README.md#setting-up-cron-task)
10. Add to `resources/domains/additionalfields.php` the following:
```
<?php
/**
* Openprovider only overrides additional fields that are configured in WHMCS Domain Pricing with Openprovider as registrar.
* Put this code above the other fields. Don't override additional fields manually for Openprovider: we maintain this for you.
*/
$additionaldomainfields = openprovider_additional_fields();
```
_**NB!** The default field definitions can be found in_ `/resources/domains/dist.additionalfields.php`. _This file should **not** be edited. To customise the fields, create a new file named_ `additionalfields.php` _within the_ `/resources/domains/` _directory._

11. Click on **Configure** and grant access to the specified roles
12. Open `clientareadomaindetails.tpl` in the template you are using and replace
```
{if $lockstatus eq "unlocked"}
```
with
```
{$domainSplit = "."|explode:$domain}
{$domainTld = $domain|replace: $domainSplit.0 : ""}
{if $lockstatus eq "unlocked" && $domainTld != '.eu' && $domainTld != '.nl' && $domainTld != '.be'  && $domainTld != '.es'}
```

## Install the DNS management options
To match the DNS types supported by Openprovider in WHMCS, you will need to install this in the template files. We have created a [request for WHMCS to make this easier. Please upvote this request here](https://requests.whmcs.com/topic/add-support-for-custom-dns-types).

_Step 1:_ Open `whmcs/templates/CURRENT_THEME_TEMPLATE/clientareadomaindns.tpl` and search for `<select name="dnsrecordtype[]" class="form-control">`. This will appear two times. 

Search for the version with the options like the one below. Note that it includes the "selected" substring:

`<option value="A"{if $dnsrecord.type eq "A"} selected="selected"{/if}>A (Address)</option>`

Replace the options with the following:
```
<option value="A"{if $dnsrecord.type eq "A"} selected="selected"{/if}>A (Address)</option>
<option value="AAAA"{if $dnsrecord.type eq "AAAA"} selected="selected"{/if}>AAAA (Address)</option>
<option value="CAA"{if $dnsrecord.type eq "CAA"} selected="selected"{/if}>CAA</option>
<option value="CNAME"{if $dnsrecord.type eq "CNAME"} selected="selected"{/if}>CNAME (Alias)</option>
<option value="MX"{if $dnsrecord.type eq "MX"} selected="selected"{/if}>MX (Mail)</option>
<option value="SPF"{if $dnsrecord.type eq "SPF"} selected="selected"{/if}>SPF (spf, not recommended)</option>
<option value="SSHFP"{if $dnsrecord.type eq "SSHFP"} selected="selected"{/if}>SSHFP</option>
<option value="SRV"{if $dnsrecord.type eq "SRV"} selected="selected"{/if}>SRV</option>
<option value="TLSA"{if $dnsrecord.type eq "TLSA"} selected="selected"{/if}>TLSA</option>
<option value="TXT"{if $dnsrecord.type eq "TXT"} selected="selected"{/if}>TXT (recommended for SPF)</option>
```

_Step 2:_ Search for the second `<select name="dnsrecordtype[]" class="form-control">`. Search for the version with the options like the one below. 

Note that this time, the "selected" substring is not used.

`<option value="A">A (Address)</option>`

Replace the options with the following:
```
<option value="A">A (Address)</option>
<option value="AAAA">AAAA (Address)</option>
<option value="CAA">CAA</option>
<option value="CNAME">CNAME (Alias)</option>
<option value="MX">MX (Mail)</option>
<option value="SPF">SPF (spf, not recommended)</option>
<option value="SSHFP">SSHFP</option>
<option value="SRV">SRV</option>
<option value="TLSA">TLSA</option>
<option value="TXT">TXT (recommended for SPF)</option>
```

_Step 3:_ Update the MX priority field with `or $dnsrecord.type eq "SRV"` in the 'if/else' block. 

This 'if' statement is used twice in the file. Replace both.

```
{if $dnsrecord.type eq "MX"} 
```


```
{if $dnsrecord.type eq "MX" or $dnsrecord.type eq "SRV"} 
```

## Configuration Option details

| Option | Description |
| ----- | ----- |
| Openprovider URL	 | `https://api.openprovider.eu` for production or `https://api.cte.openprovider.eu` for test environments. |
| Support Premium Domains| Allows you to confirm that you let the module to register domains with a premium price|
| Username | Openprovider username |
| Password | Openprovider password (**password hash is not supported**!)|
| Synchronize Expiry date | Sync the expiry date. |
| Synchronize domain status | Sync the domain status. |
| Synchronize auto renew | Sync the domain auto renew setting. |
| Synchronize identity protection | Sync the identity protection setting. |
| Synchronize due-date with offset?	| Check if you want to offset the next due dates. By doing so, your client has to pay more in advance |
| Due-date offset | The offset (by default 3) |
| Due-date max difference in days | When the difference in days between the expiry date and next due date is more than this number, the next due date is not updated. This is required to prevent that the next due date is updated when the domain is automatically renewed, but not paid for. Or, when a domain is paid for 10 years in advance but is not renewed for 10 years.  |
| Update interval | The minimum delay in hours between every domain status update (by default 2). WARNING: lowering this can overload your and Openprovider's system! |
| Domain process limit | The maximum amount of domains to process in each cron run. |
| Send empty activity reports | Send a report even when nothing has been updated in a cron run. |
| Renew domains upon transfer completion | Enter the TLDs - without a leading dot - like nl, eu separated by a comma.  Some registries offer free domain transfers, e.g. SIDN (.nl). If the expiration date is within 30 days, the domain may expiry if the renewal is not performed in time. This setting will always try to renew the TLD. |
| DNS template | Select the DNS template you prefer. NOTE: only shows up after the correct login details have been saved!|

# Openprovider Domain Sync
## Setting up cron task
In order for the module to function properly, a cron job needs to be scheduled for the script `<WHMCS directory>/modules/registrars/openprovider/cron/DomainSync.php`.

This script keeps domain statuses, expiration dates, WPP, and auto renew settings synchronized between Openprovider and WHMCS. Domain statuses and expiration dates are led by domain settings in Openprovider, and auto renew settings are led by WHMCS settings. This script updates 200 domains per execution by default (the value can be changed in the module configuration), and should be run so that all domains are synchronized within 6 hours. For example, if you have 7000 domains in WHMCS with Openprovider, the cron job needs to be run `7000 / 200 = 35` times every 6 hours, i.e. at least once every 10 minutes. (360 minutes / 35 syncs = at most 10.2 minutes/sync)

_**NB!**_ WHMCS has its own syncronisation script, `<WHMCS directory>/crons/cron.php`, which performs various maintenance activities, including invoice generation, domain statuses sync with other registrars, etc. 

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

| Openprovider | WHMCS   | Description                                                                            |
|--------------|---------|----------------------------------------------------------------------------------------|
| ACT          | Active  | The domain name is active                                                              |
| DEL          | Expired | The domain name has been deleted, but may still be restored                            |
| PEN          | Pending | The domain name request is pending further information before the process can continue |
| REQ          | Pending | The domain name request has been placed, but not yet finished                          |
| RRQ          | Pending | The domain name restore has been requested, but not yet completed                      |
| SCH          | Pending | The domain name is scheduled for transfer in the future                                |

_**NB!**_ If a domain has the status FAI in Openprovider, because of a transfer failure, then an error will be logged in the admin area as " The domain name request has failed "

### WHMCS is leading, Openprovider settings are updated:
Domain Auto-renew and ID protection settings are sent to Openprovider immediately if any changes are made from WHMCS. If something is changed in Openprovider, then the DomainSync task will ensure that auto-renew and ID settings stay synchronized.

###### 1. Domain Auto renew
1. If a domain is set to auto renew **On** in WHMCS, then the corresponding domain object in Openprovider is set to **Global Default**. If WHMCS domain object is set to auto-renew **Off** then the corresponding domain object in Openprovider is also set to **Off**.

###### 2. Domain Whois Privacy Protection (ID protection)
1. ID protection settings of a domain are mapped to Openprovider Whois Privacy Protection settings.

Any changes made to a domain by the DomainSync task will be listed in an email sent to the administrators in the group you have specified:

![alt text](http://pic001.filehostserver.eu/116671.png "Domain Sync")

# TLD Management and Check domain availability
Navigate to the "config domains" page, located at `<your WHMCS domain>/admin/configdomains.php` ​or through the menu at **Setup > Products/Services > Domain Pricing**​ where you will see (1) a place to select the lookup provider details, (2) a place to choose a TLD, (3) a dropdown to choose which domain provider to choose as the default registrar for the domain, and (4) offer ID protection, which will synchronize ID protection orders with whois privacy protection WPP.

![alt text](http://pic001.filehostserver.eu/116662.png "TLD Management and Check domain availability")

## Select a TLD and Auto Registration
1. Insert the desired TLD (2) and from the Auto Registration dropdown (3) select Openprovider
2. Click Save, and an option "Open Pricing" will appear

![alt text](http://pic001.filehostserver.eu/116663.png "Select a TLD and Auto Registration")

3. Click **Open Pricing** and define prices for register, transfer, and renewal operations in the pop-up box
4. That's it! Your WHMCS installation is ready to offer domains for registration to your clients

## Select a TLD and Auto Registration
![alt text](http://pic001.filehostserver.eu/116664.png "Select a TLD and Auto Registration")

By selecting ID protection for a given TLD, when clients purchase it on a domain, whois privacy protection (WPP) will be automatically activated in Openprovider. Note that there will be a charge from Openprovider for this service. If clients deactivate it in WHMCS, it will also be deactivated in Openprovider.

# Enable premium domains

**NB!** Make sure that the currency that you are using to pay Openprovider is configured in **Setup > Payments > Currencies** (and click on **Update Exchange Rate**). Otherwise WHMCS will not use premium fee correctly, meaning that your client pays the regular fee.

1. Navigate to **Setup > Products / Services > Domain Registrars**
2. Configure Openprovider
3. Enable **Support premium domains**
4. Save Changes.


# Choose Lookup Provider
1. Under lookup provider (1) click on **Change** and select "Openprovider" from the popup menu
2. The lookup provider should be displayed as such:

![alt text](http://pic001.filehostserver.eu/116665.png "Choose Lookup Provider")

3. All domain availability checks by your customers will now be performed using the Openprovider API.

# Admin Area
## Accepting Orders
Once a domain has been ordered by a client, a Pending order appears in the admin area on `<your WHMCS domain>/admin/orders.php` page (**Orders > Pending Orders**​). According to default WHMCS logic all orders must be approved by an administrator before being passed to the registrar. Below the client has ordered and paid for the domain "register-a-new-domain.nl" but it is yet to be approved.

![alt text](http://pic001.filehostserver.eu/116666.png "Accepting Orders")

Note from the previous section that Openprovider has been set as the registrar for auto registration for ".nl" domains. When you click **Accept order**, the order will be automatically placed at Openprovider. If there are other registrar options, there is a dropdown menu with available options.

# Managing Domains
Once a domain has been registered and activated it domain can be managed from the admin area. Navigate to **Clients > Domain Registrations** ​and select the desired domain. From there, there are options to send commands to Openprovider, including Register, Transfer, Renew, Modify Contact Details, Nameserver edits, Registrar Lock toggle and Request Delete.

![alt text](http://pic001.filehostserver.eu/116667.png "Managing Domains")

# Renewing Domains
If **Automatic renew on payment** is selected (which can be found in WHMCS admin area, **Setup > General settings > Domains**) ​and Openprovider is set as the auto registration provider, then the module will automatically register or renew the domain in Openprovider via API as soon as the client pays for domain renewal or registration.

When a domain expires in Openprovider, depending on the TLD, it can be put into 'Soft Quarantine' state. While in that state, it can be restored for a normal renewal fee, but restoration needs to be requested with the [`restoreDomainRequest`](https://doc.openprovider.eu/API_Module_Domain_restoreDomainRequest) API command. The module automatically detects when the domain is in Soft Quarantine, and makes an appropriate API request. The module will not request renewal if the domain has already passed into "Hard Quarantine" and can only be restored for an additional fee.

## Auto Renew Configurations
Default WHMCS workflow suggests that one has Auto renew set to “Off” in Openprovider RCP. This greatly reduces the chance of a domain being renewed twice unwittingly.
Please explore WHMCS documentation thoroughly before deciding on the business logic to use in your workflow.

**Scenario 1**

* Openprovider Global Auto renew - **Off**
* [WHMCS Auto Registration](https://docs.whmcs.com/Domains_Configuration#Automatic_Domain_Registration) - **On**
* [WHMCS Automatic renewal](https://docs.whmcs.com/Domains_Configuration#Automatic_Renewal) - **On**

1. End-user receives an invoice based on the due date. 
2. If the invoice is paid before expiration, WHMCS automatically sends a renewal command to the module and the domain's registration period extends.
3. If the invoice is not paid before the expiration date. The domain will expire or go into quarantine at the expiration date
4. If the grace period is supported for the given TLD and the invoice is paid after expiration, the module will send the appropriate command or commands to renew the domain.
5. If the invoice is paid when the domain is in hard quarantine, or already deleted, the module will not take action.

**Scenario 2**

* Openprovider Global Auto renew - **On**
* [WHMCS Auto Registration](https://docs.whmcs.com/Domains_Configuration#Automatic_Domain_Registration) - **On**
* [WHMCS Automatic renewal](https://docs.whmcs.com/Domains_Configuration#Automatic_Renewal) - **Off**

1. The domain will be renewed at Openprovider on the expiration date, regardless of the invoice status (paid/unpaid).
2. When the end-user pays an invoice to renew a domain, the WHMCS next due date will increment one year. 
3. No renewal commands are sent to Openprovider from WHMCS via the module.

**Scenario 3. Not recommended!**

* Openprovider Global Auto renew - **On**
* [WHMCS Auto Registration](https://docs.whmcs.com/Domains_Configuration#Automatic_Domain_Registration) - **On**
* [WHMCS Automatic renewal](https://docs.whmcs.com/Domains_Configuration#Automatic_Renewal) - **On**

1.  If the invoice is paid before expiration, WHMCS automatically sends a renewal command to the module and the domain's registration period extends.
2.  If the end-user misses payment due date, the domain will be renewed at Openprovider automatically.
2.  If the end-user pays the invoice after the due date, WHMCS will send another renewal command to the module. 

# Using Custom DNS Templates
Once you've created a custom DNS template in the Openprovider control panel (**DNS management > Manage DNS templates**), and selected it in the module configuration window, any domain created with the Openprovider nameservers will have a DNS zone automatically created on Openprovider nameservers.


# Troubleshooting
If there are any connectivity issues with Openprovider, or API commands are not working for some reason, the first troubleshooting step should be to look at the API logs. Navigate to **Utilities > Logs > Module Logs** ​or `<WHMCS directory>/admin/systemmodulelog.php`​ and you can find the raw API commands being sent and received by your WHMCS modules. The responses should contain some information about how the problem can be solved.

![alt text](http://pic001.filehostserver.eu/116668.png "Troubleshooting")

# FAQ
Common issues and solutions for them can be found [here](https://support.openprovider.eu/hc/en-us/articles/360009201193).
