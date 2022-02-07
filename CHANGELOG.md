# Changelog

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

###### Bugfixes

- 

## v5.4

###### Features and improvements

- 

###### Bugfixes

- 

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