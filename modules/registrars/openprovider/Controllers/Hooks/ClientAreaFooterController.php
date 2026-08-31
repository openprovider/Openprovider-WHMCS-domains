<?php

namespace OpenProvider\WhmcsRegistrar\Controllers\Hooks;

use OpenProvider\WhmcsRegistrar\enums\DatabaseTable;
use OpenProvider\WhmcsRegistrar\src\Configuration;
use OpenProvider\WhmcsRegistrar\helpers\DB;
use WHMCS\Database\Capsule;

class ClientAreaFooterController
{
    private const ID_NUMBER_REQUIRED_TLDS = [
        'es',
        'pt',
        'se',
        'com.es',
        'nom.es',
        'edu.es',
        'org.es',
        'it',
        'fi',
    ];

    private const ES_SECOND_LEVEL_TLDS = [
        'com.es',
        'nom.es',
        'edu.es',
        'org.es',
    ];

    public function output($vars)
    {
        GLOBAL $_LANG;

        $idnumbermod = Configuration::get('idnumbermod');

        if ($idnumbermod) {

            $template = $vars['templatefile'] ?? '';
            $js = '';
            $check_tempaltes = array('account-contacts-manage', 'account-contacts-new');
            unset($_SESSION['msg']);
            if (in_array($template, $check_tempaltes, true)) {
                $contactid = $vars['contactid'];

                if ($_SESSION['Contact_Pending_Update']) {
                    $Cord    = $_SESSION['cord'];
                    $id_Type = $_SESSION['id_type'];

                    DB::updateOrCreateContact($Cord, $contactid, $id_Type);
                    unset($_SESSION['Contact_Pending_Update']);
                }
                
                $idn = Capsule::schema()->hasTable('mod_contactsAdditional') ?
                Capsule::table(DatabaseTable::ModContactsAdditional)
                  ->where("contact_id", "=", $contactid)
                  ->first()
                  : null;

                if ($idn->contact_id) {
                    $type   = $idn->identification_type;
                    $number = $idn->identification_number;
                }

                $escapedNumber = htmlspecialchars(
                    (string) ($number ?? ''),
                    ENT_QUOTES,
                    'UTF-8'
                );

                $typePassport = $type == 'passportNumber';
                $typeCompanyRegistrationNumber = $type == 'companyRegistrationNumber';
                $typeVat = $type == 'vat';
                $typeSocialSecurityNumber = $type == 'socialSecurityNumber';

                $passport = $typePassport ? '<option selected value="passportNumber">' . $_LANG['esIdentificationPassport'] . '</option>' : '<option value="passportNumber">Individual ID</option>';
                $company = $typeCompanyRegistrationNumber ? '<option selected value="companyRegistrationNumber">' . $_LANG['esIdentificationCompany'] . '</option>' : '<option value="companyRegistrationNumber">Company Registration ID</option>';
                $vat = $typeVat ? '<option selected value="vat">' . $_LANG['ptIdentificationVat'] . '</option>' : '<option value="vat">NIPC (empresa)</option>';
                $socialSecurityNumber = $typeSocialSecurityNumber ? '<option selected value="socialSecurityNumber">' . $_LANG['esIdentificationSocialSecurityNumber'] . '</option>' : '<option value="socialSecurityNumber">NIF (particular)</option>';


                if ($passport || $typeCompanyRegistrationNumber) {
                    $js = "$('.main-content form .row .col-xs-12').each(function(index , element){ $(this).attr('data-number' , 'contactsRightDiv_'+index); }); $('.main-content form .row .col-sm-6:not(.pull-right)').each(function(index , element){ $(this).attr('data-number' , 'contactsDiv_'+index); }); $('*[data-number=\'contactsRightDiv_0\']').append('<div class=\'form-group\'><label for=\'inputTaxId\' class=\'control-label\'>" . $_LANG['esIdentificationNumber'] . "</label><input type=\'text\' name=\'cord\' id=\'cord\' class=\'form-control\' value=\'" . $escapedNumber . "\'></div>'); $('*[data-number=\'contactsDiv_1\']').append('<div class=\'form-group\'><label for=\'inputTaxId\' class=\'control-label\'>" . $_LANG['esIdentificationType'] . "</label><select id=\'id_type\' name=\'id_type\' class=\'form-control\'><option value=\'\'>Select ID Type</option>" . $passport . $company . "</select></div>');";
                } else if ($vat || $socialSecurityNumber) {
                    $js = "$('.main-content form .row .col-xs-12').each(function(index , element){ $(this).attr('data-number' , 'contactsRightDiv_'+index); }); $('.main-content form .row .col-sm-6:not(.pull-right)').each(function(index , element){ $(this).attr('data-number' , 'contactsDiv_'+index); }); $('*[data-number=\'contactsRightDiv_0\']').append('<div class=\'form-group\'><label for=\'inputTaxId\' class=\'control-label\'>" . $_LANG['ptIdentificationNumber'] . "</label><input type=\'text\' name=\'cord\' id=\'cord\' class=\'form-control\' value=\'" . $escapedNumber . "\'></div>'); $('*[data-number=\'contactsDiv_1\']').append('<div class=\'form-group\'><label for=\'inputTaxId\' class=\'control-label\'>" . $_LANG['ptIdentificationType'] . "</label><select id=\'id_type\' name=\'id_type\' class=\'form-control\'><option value=\'\'>Select ID Type</option>" . $vat . $socialSecurityNumber . "</select></div>');";
                }
            }

            if ($template === 'clientareadomaincontactinfo') {
                $domainName = $vars['domain'] ?? '';
                $tld = $this->getFullTld($domainName);

                if (!$this->isIdNumberRequiredTld($tld)) {
                    $js = '$("input[name^=\"contactdetails\"][name$=\"[Company or Individual Id]\"]").closest(".form-group").remove();';
                    $js .= '$("input[name^=\"contactdetails\"][name$=\"[Vat or Tax ID]\"]").closest(".form-group").remove();';
                } else {
                    $langKey = $this->getIdNumberLangKey($tld);

                    $idNumberName = $_LANG[$langKey] ?? 'Company or Individual ID';
                    $idNumberNameJs = json_encode($idNumberName, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);

                    $js = '$("#frmDomainContactModification").submit(function(e){
                        e.preventDefault();
                        setSessionContact();
                        $(this).unbind("submit").submit();
                    });';

                    $js .= '$("input[name^=\"contactdetails\"][name$=\"[Company or Individual Id]\"]").attr("required", true);';
                    $js .= '$("input[name^=\"contactdetails\"][name$=\"[Vat or Tax ID]\"]").attr("required", true);';

                    $js .= '$("label:contains(\"Company or Individual Id\")").text(' . $idNumberNameJs . ');';
                    $js .= '$("label:contains(\"Vat or Tax ID\")").text(' . $idNumberNameJs . ');';
                }
            }

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
        return '';
    }


    private function isIdNumberRequiredTld(string $tld): bool
    {
        return in_array(strtolower($tld), self::ID_NUMBER_REQUIRED_TLDS, true);
    }

    private function getFullTld(string $domainName): string
    {
        $domainName = strtolower($domainName);

        foreach (self::ES_SECOND_LEVEL_TLDS as $multiTld) {
            if (str_ends_with($domainName, '.' . $multiTld)) {
                return $multiTld;
            }
        }

        $labels = array_values(
            array_filter(
                explode('.', $domainName),
                static function ($label) {
                    return $label !== '';
                }
            )
        );

        if (empty($labels)) {
            return '';
        }

        return (string) end($labels);
    }

    private function getIdNumberLangKey(string $tld): string
    {
        if (in_array($tld, self::ES_SECOND_LEVEL_TLDS, true)) {
            return 'esIdentificationCORI';
        }

        return $tld . 'IdentificationCORI';
    }
}
