# TLD pricing sync utility

Openprovider supports the synchronization of the TLD pricing schemes with WHMCS. Due to the amount of TLDs supported by Openprovider, some WHMCS installations may have time-outs when synchronizing the TLD pricing. Using this guide, you can resolve these issues.

Note: loading the TLD prices still requires some time to process after running this command. WHMCS requires a significant amount of time to process all 1500+ TLDs. The speed depends on how fast your server is as well as your browser. The module only requires.

## How does this work?

### Using SSH
1. Log in using SSH to your server.
2. Navigate to `modules/registrars/openprovider/cron`
3. Execute `php -d max_execution_time=0 DownloadTldPrices.php`

_Replace php with the correct path if needed_

### Using Cron
The cron method can be used when you do not have SSH access. Once the cron has been executed, make sure to disable the cron. Otherwise, you may overload the Openprovider API with chance of reduced access.

Set the cron job to run in 5 minutes from your current time. In this example, we are assuming that it is 13:10. This means that the cron should run at 13:15. By using 5 minutes, we ensure that we have plenty of time to save before the cron should run. 

DON'T FORGOT TO DELETE THE CRON ONCE IT HAS RUN.

```
15 13 * * php -d max_execution_time=0 modules/registrars/openprovider/cron/DownloadTldPrices.php
```

_Replace php with the correct path if needed_
