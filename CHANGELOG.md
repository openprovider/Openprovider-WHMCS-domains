# Changelog

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