# Files Structure

## First files

### openprovider.php
This is the main file which finding by whmcs
From this file started processing our module and here 
placed main functions that our module have.

List all functions that used in there you can find here:
https://developers.whmcs.com/domain-registrars/function-index/

### init.php
Here we setting up our module, makes dependency injections
and makes settings for our OpenProvider api

### helpers.php
This file used for storing any helpfull functions, for example there is 
a function to define additional fields for our module

### hooks.php
Here we load hooks from Controllers/Hooks folder

## Main folders

### routes
There is a files for define routes. This files make mapping functions from whmcs
with methods that we defined in Controllers.

system.php makes mapping for Controllers/System
hooks.php makes mapping for Controllers/Hooks

### Controllers
This folder store our controllers. 
To find any method from whmcs in our Controllers we need to 
search a method in openprovider.php, then we need to find route, that 
used for this method, and then via this route we can find 
Controller and used method in this controller.

### configuration
There is files for advanced configuration this module. 

### dictionaries
This is the folder for storing dictionaries in there.

### enums
We place here enumerations classes, for example, customs database names
or openprovider errors names or something like this

### cron
In this folder we place cron scripts

### classes
This folder store only one class for idna convertion

### api 
This folder needed to make internal api calls like ajax.
This may be helpfull if we have a logic with requests in our 
custom pages or custom buttons.

in our custom page we send a request there. The url to request we can 
get using Configuration class in src folder. The method that may help you is 
getApiUrl

### import
This folder store tools to import data from csv files into whmcs
For now there is an example how to import data from powerPanel to whmcs.

### helpers
Here placed helpers that used in Controllers. It may be helpfull if we 
need to get Domain object, or check is table exists in the database

### includes
It uses for storing templates of custom pages

### migrations 
There is files for modifying database

### Models
Models

### OpenProvider
The folder where place OpenrPovider Api client and classes used for it

### src
Here placed files that used in module's main working logic

### tests
Tests

### vendor
Additional libraries

### vendor-static
Static additional libraries
