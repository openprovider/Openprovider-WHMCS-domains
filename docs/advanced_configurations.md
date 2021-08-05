# Advanced configurations



Advanced configurations can be found in the file `/modules/registrars/openprovider/configuration/advanced-module-configurations.php` Allowing you to change default values and control the behavior of your module with more granularity.

### General settings

| Parameter Name                  | Value type and default                     | Purpose                                                      |
| :------------------------------ | :----------------------------------------- | :----------------------------------------------------------- |
| api_url                         | string: "https://api.openprovider.eu/"     | The API endpoint which the module will use to connect to your Openprovider production account. |
| api_url_cte                     | string: "https://api.cte.openprovider.eu/" | The API endpoint which the module will use to connect to your Openprovider CTE account. Contact support or your account manager to get a CTE account. |
| OpenproviderPremium             | boolean: false                             | Set this value to true if you would like to sell premium domains from Openprovider. Note that you must have the same currency selected in both your WHMCS and Openprovider accounts for this to work correctly |
| require_op_dns_servers          | boolean: false                             | If your customer is using non-Openprovider nameservers, and attempting to edit DNS records, a note will be displayed encouraging them to use the correct nameservers |
| renewTldsUponTransferCompletion | string: ""(empty string)                   | Enter TLDs split by a comma (for example: "nl, eu, be") The module will alway try to renew TLDs in this list as soon as transfer is completed. This is useful for TLDs which don't include an automatic renewal with domain transfer. Note that this will incur the usual renewal cost in your Openprovider account. |
| dnsTemplate                     | string: ""(empty string)                   | Indicate the DNS template which you would like to use for all domains created via the Openprovider module. [The Openprovider Knowledge base describes how to create a template](https://support.openprovider.eu/hc/en-us/articles/216648688-How-to-use-DNS-templates) |
| useNewDnsManagerFeature         | boolean: false                             | Set to true to enable the Openprovider [single domain DNS panel](https://support.openprovider.eu/hc/en-us/articles/360014539999-Single-Domain-DNS-panel) for your customers who you have given the right to edit DNS zones for their domain. |
| requestTrusteeService                         | array: ["ba","co.uk"]     | Indicates which TLDs will automatically have the trustee option selected upon registration. |



###  Sync settings for WHMCS native sync and Openprovider cron sync

|Parameter Name|Value type and default|Purpose|
| :------------------------------ | :----------------------------------------- | :----------------------------------------------------------- |
|syncAutoRenewSetting| boolean: true | Whatever auto-renew status your client has set for the domain in WHMCS will be passed to Openprovider. if the domain has "auto-renew" status in WHMCS the domain will be set as "default" auto-renew in Openprovider. |
|syncIdentityProtectionToggle| boolean: true | Whatever identity protection status you or your client has set for the domain in WHMCS will be passed to Openprovider. Note that activating identity protection has a yearly cost in Openprovider. |



### sync settings which only apply to Openprovider cron sync

|Parameter Name|Value type and default value|Purpose|
| :------------------------------ | :----------------------------------------- | :----------------------------------------------------------- |
|syncUseNativeWHMCS| boolean: true | Select false to use the Openprovider domain sync, which offers you more granular control over the way domain are synchronized with Openprovider |
|syncDomainStatus| boolean: true | Select true to set the WHMCS domain status to reflect the Openprovider domain status |
|syncExpiryDate| boolean: true | Select true to set the WHMCS domain expiration date equal to the Openprovider expiration date |
|updateNextDueDate|boolean: true| Select true to update the next due date when the domain expiration date is updated |
|nextDueDateOffset| integer: 14 | The number of days to set the invoice due date before the domain expiration date |
|nextDueDateUpdateMaxDayDifference| integer: 100 | Due-date max difference in days. |
|updateInterval| integer: 2 | The minimum number of hours between syncs for each domain |
|domainProcessingLimit| integer: 200 | The maximum amount of domains to process in each cron run. |
|sendEmptyActivityEmail| boolean: false | Set to true to send a report even when nothing has been updated in a cron run. |
|maxRegistrationPeriod| integer: 1 | Max number of years for registration domains. |

### Placement plus settings 

|Parameter Name|Value type and default value|Purpose|
| :------------------------------ | :----------------------------------------- | :----------------------------------------------------------- |
|placementPlusAccount| string: '' | Account from placement plus to get suggested domain names from this service |
|placementPlusPassword| string: '' | Password from Account |
