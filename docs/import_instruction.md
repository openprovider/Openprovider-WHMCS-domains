#Instruction how to import data from csv

##Instruction
To import data from csv you need to go to module path, 
then into import/<data-to-import-folder> 
and then write following command:

```php import.php <csv filepath>```

After import this script generate report file, 
this file can be used to rollback changes. 

To rollback imported data you need to use import report that generated automatically after import script completed.
Rollback command:

```php rollback.php <import-report csv filepath>```

##Domain import
To import domains you need to fill configuration file ```domain-import-config.php```.
This file placed here: ```<module-folder>/import/domain-import-config.php```

###Options
 - DEFAULT_CLIENT_ID => In the case of missing client id
 - DEFAULT_CONTACT_ID => In the case of missing contact id(optional)
 - DOMAIN_STATUSES_TO_IMPORT => Allowed domain statuses to import
 - DEFAULT_PAYMENT_METHOD => Default payment method
 - NEXT_DUE_DATE_OFFSET_FROM_EXPIRY_DATE => Offset days to next due date from expiration date
 - CURRENCY_CODE => Currency code to set domain pricing