# How to install Openprovider module

## Preparing

- Upload the contents of `/modules/registrars/openprovider` from this repository to  `<your WHMCS directory>/modules/registrars/openprovider`
- Upload the contents of `/includes/hooks/` to `<your WHMCS directory>/includes/hooks`
- [For option to edit DNSSEC records] Upload contents of `<Module directory>/custom-pages` to the top level of your WHMCS folder i.e. `<your WHMCS directory>/`
- [Optional] Upload `<Module directory>/modules/addons/openprovider` to `<WHMCS directory>/modules/addons`
## Setting up
1. Navigate to **Setup > Products/Services > Domain Registrars** and activate Openprovider. 
2. Click **Save**
3. You can set DNS Templates and other advanced settings in file "advanced-module-configurations.php" by path `<WHMCS directory>/modules/registrars/openprovider/configurations/advanced-module-configurations.php`

