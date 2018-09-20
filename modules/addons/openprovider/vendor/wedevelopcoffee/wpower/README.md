# WeDevelopCoffee wPower
Thinking about creating a new WHMCS solution? Include wPower in your project and add some handy tools to your development setup. Repetitive code is added in this library and should decrease your development time.

Since WHMCS made the step to use Laravel components, we have added some more:

- Pagination (converted to Smarty template coding)

# What is included
We have included models for most tables which are based on the Eloquent model or the WHMCS model (if there is any). Some models have been extended with extra functionality.

Furthermore, migrations are a breeze. 

# How to use it
Clone the template and run `composer install` to include the extra packages. To make creating tests a bit easier, the package is designed to use automatic Dependency Injection. You are not required to use the same method (and it is a simple implementation) but it can help you with writing your tests better.

# Database
To migrate tables, use the `WeDevelopCoffee\wPower\Core\Activate->enableFeature($feature);` command when activating an module. This adds the tables to be migrated for the specific feature.

To add your own migrations, add the following in your activation code: `WeDevelopCoffee\wPower\Core\Activate->addMigrationPath($migrationPath);`. 

To migrate the tables, include the following in your activation code: `WeDevelopCoffee\wPower\Core\Activate->migrate();` The enableFeature function or addMigrationPath method can be used separately, but require both the migrate method to be ran in order to be effective.

***NOTE*** Always lead the table and migration file name after the numbers with your unique addon name.
Example:
_wrong_ `2014_10_12_100000_create_password_resets_table`
_correct_ `2014_10_12_100000_wedevelopcoffee_wpower_create_password_resets_table`

_wrong_ tbldomainplugin
_correct_ tbl_wdc_domains

Note: wdc stands for WeDevelopCoffee

# Routing
## Admin pages
If the module is designed to be a addon, you can define routes in routes/admin.php and assign the appropiate controller and function. If no function is defined, the system will default to the index function.

'routeName' => 'ControllerWithinMyNamespace@function'

## Client pages
Just like admin routes, you can do the same for clients.

## Hooks
You can put all your hooks in one big file: hooks.php. However, we have opted to create one big file that is basically an index with all the hooks used within your module. Creating a hook is a little bit different and the style is a little bit different than the regular routes since more parameters are required. In the controller key you reference the controller and this works like the regular routes. The hookPoint on the other hand is the hook name within WHMCS.

In some situations this may be a big overkill. However, it does provide more clearity.

    [
        'hookPoint' => 'theWhmcsHookPoint',
        'priority' =>  1,
        'controller' => 'indexController@index'
    ],

# Controllers
We love controllers. It allows to have better overview of the code and you will be able to find anything you want. Because of this, there are three levels of controllers:

- admin
- client
- hooks

# Templates

Once your controller is finished with the tasks, simply use `$wPower->view($view, [$arguments])` to return a view.

# Helpers
## Smarty
Note: these helpers are only available when you use the $wPower->view() method. Otherwise the additional functions will not be available in your template.

get_route action='' param=value

This will return the URL to the action and optional parameters (like param) will be added as extra GET parameters.

asset css='style.css'

Returns a stylesheet inclusion code with the css file included. The path to the file is the root of the module:
MODULE_ROOT/assets/css/style.css

asset js='script.js'

Returns a script inclusion code with the js file included. The path to the file is the root of the module:
MODULE_ROOT/assets/js/script.js

asset img='logo.png'

Returns an image inclusion code with the image file included. The path to the file is the root of the module:
MODULE_ROOT/assets/img/logo.png

If you only need the raw URL to the css, js or image file, just use asset_url instead.