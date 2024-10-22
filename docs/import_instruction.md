# Instruction how to import data from csv

Openprovider has included a number of import scripts for your convenience. Using these scripts, you can import the following from CSV files: Clients, contacts, domains, and invoices.

## Instructions

To import data from csv you need to go to module path, 
then into `import/<data-to-import-folder> `
and then write following command:

```php import.php <csv filepath>```

After import this script generate report file, 
this file can be used to rollback changes. 

To rollback imported data you need to use import report that generated automatically after import script completed.
Rollback command:

```php rollback.php <import-report csv filepath>```

## Domain import
To import domains you need to fill configuration file ```domain-import-config.php```.
This file placed here: ```<module-folder>/import/domain-import-config.php```

### Options

 - DEFAULT_CLIENT_ID => In the case of missing client id
 - DEFAULT_CONTACT_ID => In the case of missing contact id(optional)
 - DOMAIN_STATUSES_TO_IMPORT => Allowed domain statuses to import
 - DEFAULT_PAYMENT_METHOD => Default payment method
 - NEXT_DUE_DATE_OFFSET_FROM_EXPIRY_DATE => Offset days to next due date from expiration date
 - CURRENCY_CODE => Currency code to set domain pricing


# Bulk Domain Import Module

Now, Openprovider has included bulk domain import interfaces for your convenience. Using these user interfaces, you can import domains in bulk form by selecting a client and payment method. Also, you can import other registrar domains to WHMCS side without selecting resgistrar.

## Instructions

- Enable Openprovider add-on module. 
- Enable module log (for troubleshooting purposes) 
- Go to "Bulk Import" tab in Openprovider add-on page
- Enter your domains seperated by newlines (Openprovider recommend 100 domains at a time)
- Select Client, and Payment method
- Selecting a registrar is not required. (if you select "Openprovider" as the registrar, Domain sync will be executed after importing)
- Click "Import" button to import domains.

## Possible Errors
- You are have no authority to make this request. (solution: make sure you have correctly login to WHMCS) 
- No valid domains found. (soultion: make sure you have entered domains with correct format)
- Order creation failed (please refer Module log to get more details)
- Order accept failed (please refer Module log to get more details)
- Domains already exsist in WHMCS


If you have encountered an error, please refer Module log to get more details.
