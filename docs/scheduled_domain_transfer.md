# Scheduled domain transfers

Openprovider supports scheduled domain transfers. You can create them in [RCP](https://rcp.openprovider.eu) by starting a regular domain transfer with a scheduled transfer date.

If you have a number of domains to be transferred, you can use the portfolio consilidation feature in Openprovider.

## How does this work?

The module will check for pending transfers and update the registrar when the transfer has completed. Furthermore, if a pending transfer is detected, domain renewals are not passed to the former registrar but are stored instead with an updated expiry date.

### Customer renews domain while it is scheduled for transfer

The scheduled transfer will get updated to the same day. By doing so, you benefit of moving domains faster to Openprovider without having extra cost. The option "Auto Renew on Payment" should be enabled for this (Setup -> General Settings -> Domains).

### Customer disabled renewal

The scheduled domain transfer is removed.

### Customer disables and enables renewal

A todo task is created in WHMCS. A manual transfer is required.

## How to install

1. Scheduled the transfer at Openprovider
2. Activate the Openprovider addon
3. Install the cron:
`php modules/addons/openprovider/cron/Synchronise.php`
4. The addon will start fetching all transfers from now on

## FAQ

### What happens if my customer disables the autorenewal?

The scheduled domain transfer will be removed. If the customer enables the auto renewal again, a todo task will be added.

### What happens if the transfer fails?

It will continue at the former registrar. Make sure that auto renewal with this registrar is enabled.