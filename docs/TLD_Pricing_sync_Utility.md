# TLD pricing sync utility

Openprovider supports the synchronization of the TLD pricing schemes with WHMCS. Due to the amount of TLDs supported by Openprovider, some WHMCS installations may have time-outs when synchronizing the TLD pricing. Using this guide, you can resolve these issues.

## Requirements
PHP Memory Limit (memory_limit): 256M**

PHP Execution Time (max_execution_time): 300 (seconds)

Verify PHP values for your WHMCS installation from: **Utilities > System > PHP Info**.

** Memory requirements vary depending upon the size and volume of activity in a WHMCS installation. Your exact requirements may differ.

## How does this work?
You can access this feature at **Utilities > Registrar TLD Sync > Click on the Openprovider logo**. This will automatically download TLD prices from Openprovider via API and store them in file tld_cache.php if your WHMCS environment permits (PHP values and file permissions). 

**Important**: 
* Loading the TLD prices requires some time to process after clicking on the Openprovider logo. WHMCS requires a significant amount of time to process all 1500+ TLDs. The speed depends on how fast your server is as well as your browser.
* If tld_cache.php file was updated in last 24 hours, cached prices will be loaded.
* If you need to force update TLD prices, either rename/remove tld_cache.php (modules/registrars/openprovider/tld_cache.php) file or run the DownloadTldPrices.php (modules/registrars/openprovider/cron/DownloadTldPrices.php) script via SSH/Terminal or using cron as mentioned below.  

### Using SSH (only required if the above method doesn't work)
1. Log in using SSH to your server.
2. Navigate to `modules/registrars/openprovider/cron`
3. Execute `php -d max_execution_time=0 -d memory_limit=256M DownloadTldPrices.php`

### Using Cron (only required if the above method doesn't work)
The cron method can be used when you do not have SSH access. Once the cron has been executed, you may want to either remove the cron task, since it is a memory intensive task for your WHMCS server.

Set the cron job to run in 5 minutes from your current time. In this example, we are assuming that it is 13:10. This means that the cron should run at 13:15. By using 5 minutes, we ensure that we have plenty of time to save before the cron should run. 

DON'T FORGOT TO DELETE THE CRON ONCE IT HAS RUN.

```
15 13 * * * php -d max_execution_time=0 modules/registrars/openprovider/cron/DownloadTldPrices.php
```

_Replace php with the correct path if needed_
