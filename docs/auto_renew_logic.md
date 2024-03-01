# Renewing Domains

If **Automatic renew on payment** is selected (which can be found in WHMCS admin area, **Setup > General settings > Domains**) ​and Openprovider is set as the auto registration provider, then the module will automatically register or renew the domain in Openprovider via API as soon as the client pays for domain renewal or registration.

When a domain expires in Openprovider, depending on the TLD, it can be put into 'Soft Quarantine' state. While in that state, it can be restored for a normal renewal fee, but restoration needs to be requested with the [`restoreDomainRequest`](https://doc.openprovider.eu/API_Module_Domain_restoreDomainRequest) API command. The module automatically detects when the domain is in Soft Quarantine, and makes an appropriate API request. The module will not request renewal if the domain has already passed into "Hard Quarantine" and can only be restored for an additional fee.

## Auto Renew Configurations

**Important**: Default WHMCS workflow recommend switching “Off” auto-renewal in Openprovider and waiting for the client to pay for their domain renewal prior to performing any renewal action. This greatly reduces the chance of a domain being renewed twice unwittingly and incorrect domain statuses in WHMCS. Please explore WHMCS documentation thoroughly before deciding on the business logic to use in your workflow.

**Scenario 1 (WHMCS Recommended)**  

* Openprovider Global Auto renew - **Off**
* [WHMCS Auto Registration](https://docs.whmcs.com/Domains_Configuration#Automatic_Domain_Registration) - **On**
* [WHMCS Automatic renewal](https://docs.whmcs.com/Domains_Configuration#Automatic_Renewal) - **On**

1. End-user receives an invoice based on the due date. 
2. If the invoice is paid before expiration, WHMCS automatically sends a renewal command to the module and the domain's registration period extends.
3. If the invoice is not paid before the expiration date. The domain will expire or go into quarantine at the expiration date.
4. If the grace period is supported for the given TLD and the invoice is paid after expiration, the module will send the appropriate command or commands to renew the domain.
5. If the invoice is paid when the domain is in hard quarantine, or already deleted, the module will not take action.

**Scenario 2**

* Openprovider Global Auto renew - **On**
* [WHMCS Auto Registration](https://docs.whmcs.com/Domains_Configuration#Automatic_Domain_Registration) - **On**
* [WHMCS Automatic renewal](https://docs.whmcs.com/Domains_Configuration#Automatic_Renewal) - **Off**

1. The domain will be renewed at Openprovider on the expiration date, regardless of the invoice status (paid/unpaid).
2. When the end-user pays an invoice to renew a domain, the WHMCS next due date will increment one year. 
3. No renewal commands are sent to Openprovider from WHMCS via the module.

Note: If a client doesn't pay the renewal invoice before expiry date, the domain's status in WHMCS will cycle from Active to Grace >> Redemption >> Expired, even though it was auto-renewed at Openprovider.

**Scenario 3. Not recommended!**

* Openprovider Global Auto renew - **On**
* [WHMCS Auto Registration](https://docs.whmcs.com/Domains_Configuration#Automatic_Domain_Registration) - **On**
* [WHMCS Automatic renewal](https://docs.whmcs.com/Domains_Configuration#Automatic_Renewal) - **On**

1.  If the invoice is paid before expiration, WHMCS automatically sends a renewal command to the module and the domain's registration period extends.
2.  If the end-user misses payment due date, the domain will be renewed at Openprovider automatically.
3.  If the end-user pays the invoice after the due date, WHMCS will send another renewal command to the module. 
