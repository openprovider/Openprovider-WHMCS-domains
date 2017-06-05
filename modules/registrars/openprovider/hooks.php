<?php
use WHMCS\Database\Capsule,
    OpenProvider\OpenProvider;


if (!defined("WHMCS"))
{
    die("This file cannot be accessed directly");
}


/**
 * Autolaod
 * @param type $class_name
 */

spl_autoload_register(function ($className) 
{
    $className  =   implode(DIRECTORY_SEPARATOR, explode('\\', $className));
    
    if(file_exists((__DIR__).DIRECTORY_SEPARATOR.$className.'.php'))
    {
        require_once (__DIR__).DIRECTORY_SEPARATOR.$className.'.php';
    }
}); 

/**
 * Validate require fields in additional domain fields (for transfer domains)
 * 
 * @return array The array of error messages
 */
function OpenProviderAdditionalFieldsValidate()
{
    $errors = array();
    if (!isset($_SESSION['AdditionalDomainFieldRequire']))
    {
        return $errors;
    }

    $requiredFields = $_SESSION['AdditionalDomainFieldRequire'];
    foreach ($requiredFields as $requiredFieldArr)
    {
        $fieldNameArr = $requiredFieldArr['fieldName'];
        if (!empty($_SESSION['postValues'][$fieldNameArr[0]][$fieldNameArr[1]][$fieldNameArr[2]]))
        {
            continue;
        }

        $errors[] = "{$requiredFieldArr['name']} is required ({$requiredFieldArr['domain']})";
    }

    return $errors;
}

/**
 * Save $_POST values in $_SESSION and run the validate function
 */
function OpenProviderValidatDomainConfig($vars)
{
    $_SESSION['postValues'] = $_POST;

    $errors = OpenProviderAdditionalFieldsValidate();
    return $errors;
}

add_hook("ShoppingCartValidateDomainsConfig", 1, "OpenProviderValidatDomainConfig");


/**
 * Add additional domain fields to template
 */
function OpenProviderDomainAdditionalFields($vars)
{
    // for 'cart.php' only    
    if ('cart' != $vars['filename'] || @$_REQUEST['a'] != 'confdomains')
    {
        return;
    }
    
    $path = __DIR__ .DIRECTORY_SEPARATOR.'additionaldomainfields.php';
    $opCacheTable = 'OpenproviderCache';
    $opCacheFiels = 'additionalDomainFields';
    
    // compare modification time
    $fileModificationTime = date('Y-m-d H:i:s', filemtime($path));
    $cacheModificationResult = hook_domains_transfer_additional_fields_get_cache($opCacheTable, $opCacheFiels);
    $cacheModificationTime = $cacheModificationResult['timestamp'];
    
    if ($fileModificationTime > $cacheModificationTime)
    {
        // read file & update cache
        include $path;
        hook_domains_transfer_additional_fields_update_cache($opCacheTable, $opCacheFiels, $additionaldomainfields);
    }
    else
    {
        // get cache
        $additionaldomainfields = unserialize($cacheModificationResult['value']);
    }

    global $smarty;
    $domains = $smarty->_tpl_vars['domains'];

    unset($_SESSION['AdditionalDomainFieldRequire']); // info about required field will be stored in session
    // get domains to transfer
    $transferDomains = array();
    foreach ($_SESSION['cart']['domains'] as $k => $v)
    {
        if ($v['type'] == 'transfer' || $v['type'] == 'register')
        {
            $transferDomains[$k] = $v;
        }
    }

    $fields = array();
    $post = $_SESSION['postValues'];
    
    
    foreach ($transferDomains as $key => $domainArray)
    {
        $domainParts = explode(".", $domainArray['domain'], 2);
        $tmpAdditionalDomainFields = $additionaldomainfields['.' . $domainParts[1]];

        // creating fields
        for ($i = 0, $iMax = count($tmpAdditionalDomainFields); $i < $iMax; $i++)
        {
            $tmpAdditionalDomainField = $tmpAdditionalDomainFields[$i];

            // put values into session
            if (empty($post["domainfield"][$key][$i]) && !empty($_SESSION['cart']['domains'][$key]['fields'][$i]))
            {
                $_SESSION['cart']['domains'][$key]['fields'][$i] = $tmpAdditionalDomainField['Default'];
            }
            else
            {
                $_SESSION['cart']['domains'][$key]['fields'][$i] = $post["domainfield"][$key][$i];
            }

            $tmpValue = $_SESSION['cart']['domains'][$key]['fields'][$i];

            $insert = '';
            if ('dropdown' == $tmpAdditionalDomainField['Type'])
            {
                $insert .= "<select name='domainfield[$key][$i]' size='1'>";

                $optionsArray = explode(',', $tmpAdditionalDomainField['Options']);
                foreach ($optionsArray as $option)
                {
                    $insert .= "<option value='$option'";
                    $insert .= ($tmpValue == $option) ? ' selected' : '';
                    $insert .= ">$option</option>";
                }

                $insert .= '</select>';
            }
            elseif ('text' == $tmpAdditionalDomainField['Type'])
            {
                $insert .= "<input type='text' name='domainfield[$key][$i]' value='$tmpValue";
                $insert .= "' size='{$tmpAdditionalDomainField['Size']}' />";
                if ($tmpAdditionalDomainField['Required'])
                {
                    $_SESSION['AdditionalDomainFieldRequire'][] = array(
                        'name' => $tmpAdditionalDomainField['Name'],
                        'fieldName' => array(
                            "domainfield",
                            $key,
                            $i,
                        ),
                        'domain' => $domainArray['domain'],
                    );
                    $insert .= ' *';
                }
            }
            elseif ('tickbox' == $tmpAdditionalDomainField['Type'])
            {
                $insert .= "<input type='checkbox' name='domainfield[$key][$i]' value='on'";
                $insert .= ('on' == $tmpValue) ? " checked" : '';
                $insert .= ' />';
            }
            elseif ('radio' == $tmpAdditionalDomainField['Type'])
            {
                $tmpOptions = explode(',', $tmpAdditionalDomainField['Options']);
                foreach ($tmpOptions as $tmpOption)
                {
                    $insert .= '<label>';
                    $insert .= "<input type='radio' name='domainfield[$key][$i]' value='$tmpOption'";
                    $insert .= ($tmpValue == $tmpOption) ? ' checked' : '';
                    $insert .= "/>$tmpOption";
                    $insert .= '</label><br>';
                }
            }

            $fields[$tmpAdditionalDomainField['Name']] = $insert;
        }

        $domains[$key]['fields'] = $fields;
    }

    $smarty->assign('domains', $domains);
}

//add_hook("ClientAreaHeaderOutput", 1, "OpenProviderDomainAdditionalFields");


/**
 * Save additional domain fields in database (for transfer domains only) and run final validate
 * Step 1 
 */
function OpenProviderValidateCart($vars)
{
    //Let's sing... Everybody love WHMCS...
    $_SESSION['cartcopy'] = $_SESSION['cart']; 
    $errors = OpenProviderAdditionalFieldsValidate();
    return $errors;
}

add_hook("ShoppingCartValidateCheckout", 1, "OpenProviderValidateCart");

/**
 * Save additional domain fields in database (for transfer domains only)
 * Step 2 
 */
function OpenProviderSaveCart($params)
{
    if (!isset($_SESSION['cartcopy']) || !isset($_SESSION['cartcopy']['domains']) || empty($_SESSION['cartcopy']['domains']))
    {
        return;
    }

    $path = __DIR__ . DIRECTORY_SEPARATOR . DIRECTORY_SEPARATOR . 'additionaldomainfields.php';
    $opCacheTable = 'OpenproviderCache';
    
    // compare modification time
    $fileModificationTime = date('Y-m-d H:i:s', filemtime($path));
    $cacheModificationResult = hook_domains_transfer_additional_fields_get_cache($opCacheTable, 'additionalDomainFields');
    $cacheModificationTime = $cacheModificationResult['timestamp'];
    
    if ($fileModificationTime > $cacheModificationTime)
    {
        // read file & update cache
        include $path;
        hook_domains_transfer_additional_fields_update_cache($opCacheTable, 'additionalDomainFields', $additionaldomainfields);
    }
    else
    {
        // get cache
        $additionaldomainfields = unserialize($cacheModificationResult['value']);
    }

    foreach ($_SESSION['cartcopy']['domains'] as $domain)
    {
        if ($domain['type'] != 'transfer')
        {
            continue;
        }

        $q = mysql_query("SELECT id FROM tbldomains WHERE orderid='" . mysql_real_escape_string($_SESSION['orderdetails']['OrderID']) . "' AND domain='" . mysql_real_escape_string($domain['domain']) . "'");
        if (!mysql_num_rows($q))
        {
            continue;
        }

        //Domain ID
        $row = mysql_fetch_assoc($q);

        $domainParts = explode(".", $domain['domain'], 2);
        $tmpAdditionalDomainFields = $additionaldomainfields['.' . $domainParts[1]];

        if (!count($tmpAdditionalDomainFields))
        {
            continue;
        }

        for ($i = 0, $iMax = count($tmpAdditionalDomainFields); $i < $iMax; $i++)
        {
            $insertArray = array
            (
                'domainid' => $row['id'],
                'name' => $tmpAdditionalDomainFields[$i]['Name'],
                'value' => isset($domain['fields'][$i]) ? $domain['fields'][$i] : '',
            );

            insert_query('tbldomainsadditionalfields', $insertArray);
        }
    }

    unset($_SESSION['postValues']);
    unset($_SESSION['cartcopy']);
}

add_hook("AfterShoppingCartCheckout", 1, "OpenProviderSaveCart");


function hook_openproviderRemoveRecordTypes($vars)
{
    $supportedDnsTypes = \OpenProvider\API\APIConfig::$supportedDnsTypes;
    
    $finalSupportedDnsTypes = "'" . implode("','", $supportedDnsTypes) . "'";
    
    $script = 
        "$(document).ready(function () {

            // get all dns types
            var allDnsTypes = [];
            $('[name=\"dnsrecordtype[]\"] option').each(function (){
                allDnsTypes.push($(this).val());
            });

            // supported dns types
            var supportedDnsTypes = [$finalSupportedDnsTypes];

            // remove unsupported dns types
            $.each(allDnsTypes, function (key, value) {
                if (-1 === jQuery.inArray(value, supportedDnsTypes)) {
                    $('[name=\"dnsrecordtype[]\"] option[value=\"' + value + '\"]').remove();
                }
            });
        });";
    
    return "<script type=\"text/javascript\">$script</script>";
}
add_hook("ClientAreaHeaderOutput", 1, "hook_openproviderRemoveRecordTypes");



//
function hook_domains_transfer_additional_fields_get_cache($tblName, $fieldName)
{
    // create the table if it does not exist
    if (!\OpenProvider\API\APITools::tableExists($tblName))
    {
        \OpenProvider\API\APITools::createOpenprovidersTable($tblName);
    
        // insert empty row
        insert_query($tblName, array(
            'name' => $fieldName,
            'timestamp' => '',
            'value' => '',
        ));
        
        return null;
    }
    
    // get cache value
    $cacheResult = select_query($tblName, '', array(
        'name' => $fieldName,
    ));
    
    if (0 == mysql_num_rows($cacheResult))
    {
        return null;
    }
    
    return mysql_fetch_assoc($cacheResult);
}

//
function hook_domains_transfer_additional_fields_update_cache($tblName, $fieldName, $value)
{
    update_query($tblName, array(
        'value' => serialize($value),
        'timestamp' => date('Y-m-d H:i:s'),
    ), array(
        'name' => $fieldName
    ));
}

add_hook('ClientAreaPageDomainDetails', 1, 'OpenProviderSaveDomainEdit');

add_hook('DomainEdit', 1, 'OpenProviderSaveDomainEdit');

/**
 * Save the domain edits
 * 
 * @param array $vars ['userid', 'domainid']
 */
function OpenProviderSaveDomainEdit($vars)
{
    if(!isset($_POST['domain']) && !isset($_POST['autorenew']))
        return;

    // Get the domain details
    $domain = Capsule::table('tbldomains')
                ->where('id', $vars['domainid'])
                ->get()[0];

    // Check if OpenProvider is the provider
    if($domain->registrar != 'openprovider' || $domain->status != 'Active')
        return false;

    try {
        $OpenProvider       = new OpenProvider();
        $op_domain_obj      = $OpenProvider->domain($domain->domain);
        $op_domain          = $OpenProvider->api->retrieveDomainRequest($op_domain_obj);
        $OpenProvider->toggle_autorenew($domain, $op_domain);
    } catch (Exception $e) {
        \logModuleCall('OpenProvider', 'Save domain lock', $domain->domain, @$op_domain, $e->getMessage(), [$params['Password']]);
        return false;
    }
}