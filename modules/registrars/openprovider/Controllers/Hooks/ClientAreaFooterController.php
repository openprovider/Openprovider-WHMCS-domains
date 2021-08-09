<?php

namespace OpenProvider\WhmcsRegistrar\Controllers\Hooks;

use OpenProvider\WhmcsRegistrar\enums\DatabaseTable;
use OpenProvider\WhmcsRegistrar\src\Configuration;
use OpenProvider\WhmcsRegistrar\helpers\DB;
use WHMCS\Database\Capsule;

class ClientAreaFooterController
{
    public function output($vars)
    {
        GLOBAL $_LANG;

        $idnumbermod = Configuration::get('idnumbermod');

        if ($idnumbermod) {

            $template        = $vars['templatefile'];
            $check_tempaltes = array('account-contacts-manage', 'account-contacts-new');
            $msg             = $_SESSION['msg'];
            unset($_SESSION['msg']);
            if (in_array($template, $check_tempaltes)) {
                $contactid = $vars['contactid'];

                if ($_SESSION['Contact_Pending_Update']) {
                    $Cord    = $_SESSION['cord'];
                    $id_Type = $_SESSION['id_type'];

                    DB::updateOrCreateContact($Cord, $contactid, $id_Type);
                    unset($_SESSION['Contact_Pending_Update']);
                }

                $idn = Capsule::table(DatabaseTable::ModContactsAdditional)
                    ->where("contact_id", "=", $contactid)
                    ->first();

                if ($idn->contact_id) {
                    $type   = $idn->identification_type;
                    $number = $idn->identification_number;
                }

                $typePassport = $type == 'passportNumber';
                $typeCompanyRegistrationNumber = $type == 'companyRegistrationNumber';
                $typeVat = $type == 'vat';
                $typeSocialSecurityNumber = $type == 'socialSecurityNumber';

                $passport = $typePassport ? '<option selected value="passportNumber">' . $_LANG['esIdentificationPassport'] . '</option>' : '<option value="passportNumber">Individual ID</option>';
                $company = $typeCompanyRegistrationNumber ? '<option selected value="companyRegistrationNumber">' . $_LANG['esIdentificationCompany'] . '</option>' : '<option value="companyRegistrationNumber">Company Registration ID</option>';
                $vat = $typeVat ? '<option selected value="vat">' . $_LANG['ptIdentificationVat'] . '</option>' : '<option value="vat">NIPC (empresa)</option>';
                $socialSecurityNumber = $typeSocialSecurityNumber ? '<option selected value="socialSecurityNumber">' . $_LANG['esIdentificationSocialSecurityNumber'] . '</option>' : '<option value="socialSecurityNumber">NIF (particular)</option>';


                if ($passport || $typeCompanyRegistrationNumber) {
                    $js = "$('.main-content form .row .col-xs-12').each(function(index , element){ $(this).attr('data-number' , 'contactsRightDiv_'+index); }); $('.main-content form .row .col-sm-6:not(.pull-right)').each(function(index , element){ $(this).attr('data-number' , 'contactsDiv_'+index); }); $('*[data-number=\'contactsRightDiv_0\']').append('<div class=\'form-group\'><label for=\'inputTaxId\' class=\'control-label\'>" . $_LANG['esIdentificationNumber'] . "</label><input required type=\'text\' name=\'cord\' id=\'cord\' class=\'form-control\' value=\'" . $number . "\'></div>'); $('*[data-number=\'contactsDiv_1\']').append('<div class=\'form-group\'><label for=\'inputTaxId\' class=\'control-label\'>" . $_LANG['esIdentificationType'] . "</label><select id=\'id_type\' required name=\'id_type\' class=\'form-control\'><option value=\'\'>Select ID Type</option>" . $passport . $company . "</select></div>');";
                } else if ($vat || $socialSecurityNumber) {
                    $js = "$('.main-content form .row .col-xs-12').each(function(index , element){ $(this).attr('data-number' , 'contactsRightDiv_'+index); }); $('.main-content form .row .col-sm-6:not(.pull-right)').each(function(index , element){ $(this).attr('data-number' , 'contactsDiv_'+index); }); $('*[data-number=\'contactsRightDiv_0\']').append('<div class=\'form-group\'><label for=\'inputTaxId\' class=\'control-label\'>" . $_LANG['ptIdentificationNumber'] . "</label><input required type=\'text\' name=\'cord\' id=\'cord\' class=\'form-control\' value=\'" . $number . "\'></div>'); $('*[data-number=\'contactsDiv_1\']').append('<div class=\'form-group\'><label for=\'inputTaxId\' class=\'control-label\'>" . $_LANG['esIdentificationType'] . "</label><select id=\'id_type\' required name=\'id_type\' class=\'form-control\'><option value=\'\'>Select ID Type</option>" . $passport . $company . "</select></div>');";
                }
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
    }
}
