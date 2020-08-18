# Changelog
## DOMAIN TRANSFER




## V3.3
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

## V3.1

- Feature: Execute renewal action for free domain transfers (#126)
- Bugfix: Removed the by WHMCS introduced field "Eurid Entity type" for .eu (#130)
- Bugfix: Fixed addon activation (#119)
- Bugfix: Domains in Pending transfer are skipped by DomainSync (#131)


## All previous versions to V3.0

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