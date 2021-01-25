# How to install openprovider module

## Preparing

1. Download the module
2. Upload `<Module directory>/modules/registrars/openprovider` to `<WHMCS directory>/modules/registrars`
3. Upload `<Module directory>/includes/hooks/*` to `<WHMCS directory>/includes/hooks/*`
4. Upload `<Module directory>/includes/*.php` to `<WHMCS directory>/includes/*.php`
5. [Optional] Upload `<Module directory>/modules/addons/openprovider` to `<WHMCS directory>/modules/addons`
6. Upload all files from `<Module directory>/custom-pages/*.php` to `<WHMCS directory>/*.php`
   
## Setting up
1. Navigate to **Setup > Products/Services > Domain Registrars** and activate Openprovider. Use `https://api.openprovider.eu` as the API url.
2. Click **Save**
3. You can set DNS Templates and other advanced settings in file "advanced-module-configurations.php" by path `<WHMCS directory>/modules/registrars/openprovider/configurations/advanced-module-configurations.php`

