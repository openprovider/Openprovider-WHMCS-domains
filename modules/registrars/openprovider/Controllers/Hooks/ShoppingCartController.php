<?php

namespace OpenProvider\WhmcsRegistrar\Controllers\Hooks;

use OpenProvider\WhmcsRegistrar\src\Configuration;
use OpenProvider\WhmcsRegistrar\helpers\DB;

class ShoppingCartController
{
    public function checkoutOutput($vars)
    {
        global $_LANG;
        $idnumbermod = Configuration::get('idnumbermod');
        if ($idnumbermod) {
            $domainsToMatch = ['es', 'pt'];
            $data = []; // Initialize $data array

            // Check if domains exist
            if (!empty($vars['cart']['domains'])) {
                foreach ($vars['cart']['domains'] as $domain) {
                    $tld = explode('.', $domain['domain']);
                    $f = 0;
                    $fieldData = [];

                    // Check if fields exist
                    if (!empty($domain['fields']) && is_array($domain['fields'])) {
                        foreach ($domain['fields'] as $field) {
                            $f++;
                            if (isset($tld[1]) && in_array($tld[1], $domainsToMatch)) {
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
                    }

                    if (!empty($fieldData)) {
                        $data[$domain['domain']] = $fieldData;
                    }
                }
            }

            $fieldDisplay = ''; // Initialize $fieldDisplay

            foreach ($data as $domain => $fields) {
                if (empty($fields['field']) || empty($fields['value'])) {
                    continue;
                }

                $name = '';
                switch ($fields['field']) {
                    case 'companyRegistrationNumber':
                        $name = $_LANG['esIdentificationCompany'];
                        break;
                    case 'passportNumber':
                        $name = $_LANG['esIdentificationPassport'];
                        break;
                    case 'vat':
                        $name = $_LANG['ptIdentificationVat'];
                        break;
                    case 'socialSecurityNumber':
                        $name = $_LANG['ptIdentificationSocialSecurityNumber'];
                        break;
                }

                $fieldDisplay .= "<div class='col-sm-12'><div class='form-group prepend-icon'><label class='field-icon' for='" . $domain . "_" . $fields["field"] . "'> <i class='fas id-card'></i></label><input required class='form-control' readonly id='" . $domain . "_" . $fields["field"] . "' type='text' name='" . $fields["field"] . "' value='[$domain] $name:  " . $fields["value"] . "' /> </div></div>";
            }

            if (!empty($fieldDisplay)) {
                $output = '<script type="text/javascript">$("#domainRegistrantInputFields").append("' . $fieldDisplay . '")</script>';
                return $output;
            }
        }

        return ''; // Return empty string if no output
    }
    
    public function preCheckout($vars)
    {
        $idnumbermod = Configuration::get('idnumbermod');

        if ($idnumbermod) {
            $domainsToMatch = array('es', 'pt');
            $contactid      = $vars['contact'];

            foreach ($vars['domains'] as $domain) {

                $tld = explode('.', $domain['domain']);

                if (in_array($tld[1], $domainsToMatch)) {
                    $fieldData = array();
                    foreach ($domain['fields'] as $field) {
                        if (
                            $field == 'passportNumber' ||
                            $field == 'companyRegistrationNumber' ||
                            $field == 'vat' ||
                            $field == 'socialSecurityNumber'
                        ) {
                            $fieldData['field'] = $field;
                        }

                        if (!empty($fieldData['field'])) {
                            $fieldData['value'] = $field;
                        }
                    }

                    if (!empty($fieldData['value']) && !empty($fieldData['field']) && !empty($contactid)) {
                        DB::updateOrCreateContact($fieldData['value'], $contactid, $fieldData['field']);
                    }
                }
            }
        }
    }
}
