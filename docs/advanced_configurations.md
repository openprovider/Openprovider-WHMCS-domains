# Advanced configurations



Advanced configurations can be found in the file `/modules/registrars/openprovider/configuration/advanced-module-configurations.php` Allowing you to change default values and control the behavior of your module with more granularity.

### General settings

| Parameter Name                  | Value type and default                     | Purpose                                                      |
| :------------------------------ | :----------------------------------------- | :----------------------------------------------------------- |
| api_url                         | string: "https://api.openprovider.eu/"     | The API endpoint which the module will use to connect to your Openprovider production account. |
| restapi_url_sandbox             | string: "http://api.sandbox.openprovider.nl:8480" | The API endpoint which the module will use to connect to your Openprovider Sandbox (CTE) account. You can create a sandbox account at https://cp.sandbox.openprovider.nl/signup. |
| OpenproviderPremium             | boolean: **read-only**                            | **Do not edit this value directly**. It is synchronized from the WHMCS Admin Premium Domains setting (Configuration > System Settings > Domain Pricing). Use the Admin UI to enable or disable Premium Domain sales. Make sure that the currency is the same in both WHMCS and Openprovider for correct pricing |
| require_op_dns_servers          | boolean: false                             | If your customer is using non-Openprovider nameservers, and attempting to edit DNS records, a note will be displayed encouraging them to use the correct nameservers |
| renewTldsUponTransferCompletion | string: ""(empty string)                   | Enter TLDs split by a comma (for example: "nl, eu, be") The module will alway try to renew TLDs in this list as soon as transfer is completed. This is useful for TLDs which don't include an automatic renewal with domain transfer. Note that this will incur the usual renewal cost in your Openprovider account. |
| dnsTemplate                     | string: ""(empty string)                   | Indicate the DNS template which you would like to use for all domains created via the Openprovider module. [The Openprovider Knowledge base describes how to create a template](https://support.openprovider.eu/hc/en-us/articles/216648688-How-to-use-DNS-templates) |
| useNewDnsManagerFeature         | boolean: false                             | Set to true to enable the Openprovider [single domain DNS panel](https://support.openprovider.eu/hc/en-us/articles/360014539999-Single-Domain-DNS-panel) for your customers who you have given the right to edit DNS zones for their domain. |
| requestTrusteeService           | array: ["ba","co.uk"]                      | Indicates which TLDs will automatically have the trustee option selected upon registration. |
| idnumbermod                     | boolean: false                             | Indicates whether to use the [advanced additional data handling for .es and .pt domains](/docs/advanced_additional_data.md) |



###  Sync settings for WHMCS native sync and Openprovider cron sync

|Parameter Name|Value type and default|Purpose|
| :------------------------------ | :----------------------------------------- | :----------------------------------------------------------- |
| renewalDateSync                 | boolean: true                              | Sync Openprovider renewal dates to WHMCS as expiration date. This will avoid mismatch in dates due to renewal offset. |
|syncAutoRenewSetting| boolean: true | Whatever auto-renew status your client has set for the domain in WHMCS will be passed to Openprovider. if the domain has "auto-renew" status in WHMCS the domain will be set as "default" auto-renew in Openprovider. |
|syncIdentityProtectionToggle| boolean: true | Whatever identity protection status you or your client has set for the domain in WHMCS will be passed to Openprovider. Note that activating identity protection has a yearly cost in Openprovider. |



### Sync settings which only apply to Openprovider cron sync [DEPRECATED**]

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

** The Openprovider cron sync is deprecated since version 5.3 of the Openprovider domain module, and is not recommended for versions WHMCS 8+. We suggest that you use the WHMCS native domain sync and do not use the Openprovider custom sync for WHMCS 8 and higher.
