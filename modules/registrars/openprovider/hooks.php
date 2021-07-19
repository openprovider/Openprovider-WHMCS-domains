<?php
/**
 * wPower Boostrap
 *
 * @copyright Copyright (c) WeDevelopCoffee 2018
 */

use WeDevelopCoffee\wPower\Module\Setup;
use Illuminate\Database\Capsule\Manager as Capsule;

if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

require_once(__DIR__ .'/init.php');

$core = openprovider_registrar_core();

$core->launch()
    ->hooks();

$core->launcher = openprovider_bind_required_classes($core->launcher);

$activate = $core->launcher->get(Setup::class);
$activate->enableFeature('handles');
$activate->addMigrationPath(__DIR__.'/migrations');
$activate->migrate();

add_hook('ShoppingCartCheckoutOutput', 1, function($vars) {
    GLOBAL $_LANG;

    $params = require('configuration/advanced-module-configurations.php');

    $esmodification = $params['esmod'];

    if ($esmodification == "on") {
        $domainsToMatch = ['es'];

        foreach ($vars['cart']['domains'] as $domain) {
            $tld       = explode('.', $domain['domain']);
            $f         = 0;
            $fieldData = [];
            foreach ($domain['fields'] as $field) {
                $f++;
                if (in_array($tld[1], $domainsToMatch)) {
                    switch ($f) {
                        case 1:
                            $fieldData['field'] = $field;
                            break;
                        case 2:
                            $fieldData['value'] = $field;
                            break;
                    }
                }
            }

            $data[$domain['domain']] = $fieldData;
        }

        foreach ($data as $domain => $fields) {

            switch ($fields['field']) {
                case 'companyRegistrationNumber':
                    $name = $_LANG['esIdentificationCompany'];
                    break;
                case 'passportNumber':
                    $name = $_LANG['esIdentificationPassport'];
                    break;
            }

            $fieldDisplay .= "<div class='col-sm-12'><div class='form-group prepend-icon'><label class='field-icon' for='" . $domain . "_" . $fields["field"] . "'> <i class='fas id-card'></i></label><input required class='form-control' readonly id='" . $domain . "_" . $fields["field"] . "' type='text' name='" . $fields["field"] . "' value='[$domain] $name:  " . $fields["value"] . "' /> </div></div>";
        }

        $output = '<script type="text/javascript">$("#domainRegistrantInputFields").append("' . $fieldDisplay . '")</script>';

        return $output;
    }
});


add_hook('ContactAdd', 1, function ($vars) {
    $params = require('configuration/advanced-module-configurations.php');

    $esmodification = $params['esmod'];

    if ($esmodification == "on") {

        $idtype = $_POST['id_type'];
        $cord   = $_POST['cord'];

        queueContactUpdate($idtype, $cord);
    }
});

add_hook('PreShoppingCartCheckout', 1, function ($vars) {
    $params = require('configuration/advanced-module-configurations.php');

    $esmodification = $params['esmod'];

    if ($esmodification == "on") {
        $domainsToMatch = array('es');
        $contactid      = $vars['contact'];

        foreach ($vars['domains'] as $domain) {

            $tld = explode('.', $domain['domain']);

            if (in_array($tld[1], $domainsToMatch)) {
                $fieldData = array();
                foreach ($domain['fields'] as $field) {
                    if ($field == 'passportNumber' || $field == 'companyRegistrationNumber') {
                        $fieldData['field'] = $field;
                    }

                    if (!empty($fieldData['field'])) {
                        $fieldData['value'] = $field;
                    }
                }

                if (!empty($fieldData['value']) && !empty($fieldData['field']) && !empty($contactid)) {
                    updateOrCreateContact($fieldData['value'], $contactid, $fieldData['field']);
                }
            }
        }
    }
});


add_hook('ContactDelete', 1, function ($vars) {

    $params = require('configuration/advanced-module-configurations.php');

    $esmodification = $params['esmod'];

    if ($esmodification == "on") {
        verify_contactstables();
    }
});

add_hook('ContactEdit', 1, function($vars) {
    $params = require('configuration/advanced-module-configurations.php');

    $esmodification = $params['esmod'];

    if ($esmodification == "on") {
        verify_contactstables();

        $cord      = $_POST['cord'];
        $contactid = $_POST['contactid'];
        $id_type   = $_POST['id_type'];

        updateOrCreateContact($cord, $contactid, $id_type);
    }
});

add_hook('ClientAreaFooterOutput', 1, function($vars) {
    GLOBAL $_LANG;

    $params = require('configuration/advanced-module-configurations.php');

    $esmodification = $params['esmod'];

    if ($esmodification == "on") {

        $template        = $vars['templatefile'];
        $check_tempaltes = array('account-contacts-manage', 'account-contacts-new');
        $msg             = $_SESSION['msg'];
        unset($_SESSION['msg']);
        if (in_array($template, $check_tempaltes)) {
            $contactid = $vars['contactid'];

            if ($_SESSION['Contact_Pending_Update']) {
                $Cord    = $_SESSION['cord'];
                $id_Type = $_SESSION['id_type'];

                updateOrCreateContact($Cord, $contactid, $id_Type);
                unset($_SESSION['Contact_Pending_Update']);
            }

            $idn = Capsule::table('mod_contactsAdditional')
                ->where("contact_id", "=", $contactid)
                ->first();

            if ($idn->contact_id) {
                $type   = $idn->identification_type;
                $number = $idn->identification_number;

            }

            $passport = ($type == 'passportNumber') ? '<option selected value="passportNumber">' . $_LANG['esIdentificationPassport'] . '</option>' : '<option value="passportNumber">Individual ID</option>';
            $company  = ($type == 'companyRegistrationNumber') ? '<option selected value="companyRegistrationNumber">' . $_LANG['esIdentificationCompany'] . '</option>' : '<option value="companyRegistrationNumber">Company Registration ID</option>';


            $js = "$('.main-content form .row .col-xs-12').each(function(index , element){ $(this).attr('data-number' , 'contactsRightDiv_'+index); }); $('.main-content form .row .col-sm-6:not(.pull-right)').each(function(index , element){ $(this).attr('data-number' , 'contactsDiv_'+index); }); $('*[data-number=\'contactsRightDiv_0\']').append('<div class=\'form-group\'><label for=\'inputTaxId\' class=\'control-label\'>" . $_LANG['esIdentificationNumber'] . "</label><input required type=\'text\' name=\'cord\' id=\'cord\' class=\'form-control\' value=\'" . $number . "\'></div>'); $('*[data-number=\'contactsDiv_1\']').append('<div class=\'form-group\'><label for=\'inputTaxId\' class=\'control-label\'>" . $_LANG['esIdentificationType'] . "</label><select id=\'id_type\' required name=\'id_type\' class=\'form-control\'><option value=\'\'>Select ID Type</option>" . $passport . $company . "</select></div>');";
        }

        if ($template == 'clientareadomaincontactinfo') {
            $js = '$("#frmDomainContactModification").submit(function(e){ e.preventDefault(); setSessionContact();  $(this).unbind("submit").submit(); }); $("input[name=\"contactdetails[Owner][Company or Individual Id]\"]").attr("required" , true); $("input[name=\"contactdetails[Admin][Company or Individual Id]\"]").attr("required" , true); $("input[name=\"contactdetails[Tech][Company or Individual Id]\"]").attr("required" , true); $("input[name=\"contactdetails[Billing][Company or Individual Id]\"]").attr("required" , true); $("label:contains(\"Company or Individual Id\")").html("' . $_LANG['esIdentificationCORI'] . '");';
        }

        $page      = $_SERVER['PHP_SELF'];
        $systemurl = $vars['systemurl'];

    return <<<HTML

<script type="text/javascript">
{$js}

function setSessionContact(){

  var ownerenable   =   $("input[name='wc[Owner]']:checked").val();
  var adminenable   =   $("input[name='wc[Admin]']:checked").val();
  var techenable    =   $("input[name='wc[Tech]']:checked").val();
  var billingenable =   $("input[name='wc[Billing]']:checked").val();

  if(ownerenable == 'contact')
  {
    var owner = $("#Owner3").val();
  }

  if(adminenable == 'contact')
  {
    var admin = $("#Admin3").val();
  }

  if(techenable == 'contact')
  {
    var tech = $("#Tech3").val();
  }

  if(billingenable == 'contact')
  {
    var billing = $("#Billing3").val();
  }

var request = $.ajax({
  url: '{$systemurl}/modules/registrars/openprovider/contactsession.php',
  method: "POST",
  data: { 'owner' : owner, 'admin' : admin , 'tech' :tech , 'billing' : billing , 'set' : 'contactsession' },
});

request.done(function( msg ) {
  // alert(msg);
  var success = 1;
  return success;
});

request.fail(function( jqXHR, textStatus ) {
  alert( "Request failed: " + textStatus );
});

}
</script>
HTML;
    }
});

function verify_contactstables(){
    if (!Capsule::schema()->hasTable('mod_contactsAdditional')) {
        //CREATE TABLES
        try {
            Capsule::schema()
                ->create(
                    'mod_contactsAdditional',
                    function ($table) {
                        $table->increments('id');
                        $table->integer('contact_id');
                        $table->text('identification_type');
                        $table->text('identification_number');
                    }
                );
        } catch (\Exception $e) {
            return [
                // Supported values here include: success, error or info
                'status'      => "error",
                'description' => 'An error occured while creating tables: ' . $e->getMessage(),
            ];
        }
    }
}

function updateOrCreateContact($cord, $contactid, $id_type)
{
    $idn = Capsule::table('mod_contactsAdditional')
        ->where("contact_id", "=", $contactid)
        ->first();

    if ($idn->contact_id) {
        try {
            $updatedUserCount = Capsule::table('mod_contactsAdditional')
                ->where('contact_id', $contactid)
                ->update(
                    [
                        'identification_type'   => $id_type,
                        'identification_number' => $cord,

                    ]
                );

            $msg = "Updated {$updatedUserCount} Contact";

        } catch (\Exception $e) {
            $msg = "I couldn't update Contact Company or Individual ID: . {$e->getMessage()}";
        }
    } else {
        try {
            Capsule::table('mod_contactsAdditional')->insert(
                [
                    'contact_id'            => $contactid,
                    'identification_number' => $cord,
                    'identification_type'   => $id_type,
                ]
            );

            $msg = "Updated {$updatedUserCount} Contact";

        } catch (\Exception $e) {
            $msg = "Uh oh! I was unable to update Contacts Company or Individual ID, but I was able to rollback. {$e->getMessage()}";
        }
    }

}

function queueContactUpdate($idtype , $cord , $contactid = null)
{
    $_SESSION['Contact_Pending_Update'] = true;
    $_SESSION['id_type']                = $idtype;
    $_SESSION['cord']                   = $cord;
}
