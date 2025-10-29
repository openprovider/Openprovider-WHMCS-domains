# Changelog

## v5.8.6

###### Features and improvements
* Implemented ICANN's Registration Data Policy(RDP): Minimum Data Set (MDS) changes

###### Bugfixes
* Fixed contact update failure for existing domains due to Minimum Data Set changes

## v5.8.5

### Features and improvements
* Added **.SE/.NU Terms & Conditions Acceptance** checkbox - IIS (.SE/.NU) Registry made changes to the flow of .se/.nu domain names, making it mandatory for domain owners to accept T&C's of the registry before registering a .se or .nu domain name.

## v5.8.4

### Features and improvements
* Added a **Consent to Publish Domain Contact Data** option for gTLDs in domain registration, transfer, and and domain modification (WHMCS Admin Area) pages - Personal data is redacted by default to protect registrants privacy according to GDPR. Explicit consent is now required to publish private contact details publicly to registrar WHOIS/RDAP.

## v5.8.3

###### Features and improvements
* Added .es transfer auth code support (ensure to select **[EPP Code](https://docs.whmcs.com/domains/pricing-and-configuration/domain-pricing/#epp-code)** checkbox for .es in **Domain Pricing** to require an EPP code for incoming transfers of .es domains). 
* Added .dk Terms & Conditions Acceptance checkbox - .DK Registry made changes to the flow of .DK domain names, making it mandatory for domain owners to accept T&C's of the registry before registering a .dk domain name. 
* Generate a new auth code on clicking 'Get EPP Code' button in WHMCS if domain auth code is empty (for supported TLDs).

###### Bugfixes
* Fixed: Resolved an issue with converting checkbox values to strings.
* Fixed error accessing admin dashboard - `Argument #2 ($array) must be of type array, null given in ../DocBlock/Tags/InvalidTag.php:89`
* Updated the getOrDefault() function and default value of 'require_op_dns_servers' in advanced-module-configurations.php file.

## v5.8.0

###### Features and improvements
- **Improved [TLD Price Sync](https://github.com/openprovider/Openprovider-WHMCS-domains/blob/version-8/docs/TLD_Pricing_sync_Utility.md)** - Fetch TLD prices with a single click from WHMCS. If prices were fetched via within last 24 hours, we will use the downloaded prices to speed up the operation.
- **Improved [DNSSEC management for end users](https://github.com/openprovider/Openprovider-WHMCS-domains/blob/version-8/docs/DNSSEC_for_clients.md)** - Manually copying contents from 'custom-pages' to root folder is no longer required if your hosting environment permits. Made it easier to manage DNSSEC.
- **Improved error reporting and logging** - Errors will be shown in WHMCS without restrictions. New informative error messages added for some scenarios.
- **Improved Configuration Validation** - The module now uses WHMCS recommended `_config_validate` function to check whether the credentials and environment are correct.
- **Auto-renew** - Domains will be created with auto-renew value "default" (will inherit reseller account settings)
- **New**: [To-Do List](https://docs.whmcs.com/admins/admin-tools/#to-do-list) items creation for domain operation warnings in success response.


###### Bugfixes
- Fixed known PHP 8.1 compatibility issues (Renewal, double-renewal, TLD sync, cron, additional fields, whois lookup, domain suggestions
- Fixed errors getting truncated
- Fixed duplicate handle creation on contact update
- Fixed XML parse error for domain registration in Sandbox
- Fixed error renewing domains in Grace Period (Error: This domain already exists in Openprovider but NOT active)
- Fixed Scheduled Domain Transfer sync script
- Fixed nameserver IP update problem from WHMCS client area (Error: Field apiClass not found into command mapping!)
- Fixed balance widget (can't close or hide)
- Fixed domain renewal conflict when using other registrar modules

## v5.6

###### Features and improvements

- Allow end user to enter additional fields for domain .it(company registration number/vat number/social security number). 
- Enhanced module logs.

###### Bugfixes

- Improved authentication performance.
- Improved language supportive with IPA(Phonetic) characters.
- Fixed table missing error in customer page

## v5.5

###### Features and improvements

- Allow end user to enter additional fields for domain .es(owner type/company registration number or identification number). 
- Enhanced module logs.

## v5.4

###### Features and improvements

- Improves activation logic for DNSSEC management
- Improves sidebar logic for DNSSEC and makes adding translations simpler
- Improves TLD price download script with more verbose error reporting

###### Bugfixes

- Fixes issue where transfers would sometimes fail because of missing DNS zones
- Fixes issue which would cause domain pricing sync to sometimes fail

## v5.3

###### Features and improvements

- Allow end users and WHMCS administrators to update domain contact  details for .es domains, including the company / personal identification number

###### Bugfixes

- Fixes issue with DNSSEC domain validation

## v5.0

###### Features and improvements

- Migrated the module from the Openprovider XML API to use the Openprovider REST API.
- Control the maximum period for price imports.
- Added option for partners to use Placement+ domain search

###### Bugfixes

- various bugfixes and improvements
- Fixes issue where WHMCS domain sync would not update domain expiration dates in some cases

## v4.0.3

###### Bugfixes

- Fixes issue with DNSSEC domain validation

## v4.0.2

###### Bugfixes

- Fixes issue with unnecessary variable type
- Fixes "getting oops error with mixed registrars"
- Updates menu item & API scripts' absolute path

## v4.0.1

###### Features

- Added support for Domain contact verification, for more information see the [WHMCS documentation](https://docs.whmcs.com/Domain_Contact_Verification)
- [Support for tag management of clients](https://github.com/openprovider/OP-WHMCS7#allow-end-users-to-edit-dnssec-records)
- Simplified configuration page
- [Allow end users to manage DNSSEC records](https://github.com/openprovider/OP-WHMCS7#allow-end-users-to-edit-dnssec-records)
- Refactored documentation

###### Bugfixes

- Fixes issue with unnecessary variable type

## v3.4.1

- [FEATURE] Added support for TLD price caching.
- [IMPROVEMENT] Simplified login page and moved advanced configuration options to a file
- [BUGFIX] Incorrect credentials were sometimes not being indicated when activating the module
- [FEATURE] Allow end users to manage DNSSEC settings on their domains
- [FEATURE] Added support for WHMCS feature domain verification as described in [the WHMCS documentaitno](https://docs.whmcs.com/Domain_Contact_Verification)
- [FEATURE] Adds support for assigning tags to specific clients, which allows the WHMCS administrator to send custom emails to the client and all of the client's contacts

## v3.3
Notes:
To improve DNS support, perform the actions are instructed in "Install the DNS management options". We have created a [request for WHMCS to make this easier. Please upvote this request here](https://requests.whmcs.com/topic/add-support-for-custom-dns-types).

- Feature: #128 - Import domain pricing (WHMCS 7.10)
- Feature: #131 - Reach DNS record type parity with Openprovider
- Feature: #146 Changed how synchronisation works. Uses native WHMCS function and synchronises expired domains separatedly.
- Feature: #149 Show the version number in the configuration page.
- Feature: #144 Add support for GetDomainInformation
- Feature: #125 Warn users about setting the correct DNS servers when enabling DNS management
- Feature: #84 - Initial support for next-gen DNS.
- Improvement: #132 Remove non-required .de required fields
- Improvement: #148 Skip domains marked as fraud in synchronisation

## v3.1

- Feature: Execute renewal action for free domain transfers (#126)
- Bugfix: Removed the by WHMCS introduced field "Eurid Entity type" for .eu (#130)
- Bugfix: Fixed addon activation (#119)
- Bugfix: Domains in Pending transfer are skipped by DomainSync (#131)


## All previous versions to v3.0

Manual action required: Check step 12 of hte installation manual in order to remove the domain lock warning for certain TLDs.

- Feature: Added more options for what should be synced.
- Feature: Widget shows in the admin dashboard the number of domains and the credit with OpenProvider.
- Improvement: #99 remove domain lock notification for certain TLDs.
- Improvement: Removed outdated fields for .pro
- Improvement: Made a change to the whois protection API implementation.
- Improvement: Refactored the structure partially. Started implementing tests.
- Bugfix: bug where clients can update the reseller data
- Bugfix: Replaced next due date sync engine
- BugFix: for selling premium domains where the incorrect buying price was passed on.
- Bugfix: Too few arguments to function Handle::update()
- Bugfix: Delete domain prior to zone deletion in requestDelete()

Known bugs in WHMCS:

- Module shouldn't convert contact data to ASCI (CORE-13427)
