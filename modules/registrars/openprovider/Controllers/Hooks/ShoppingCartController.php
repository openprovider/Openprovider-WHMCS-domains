<?php

namespace OpenProvider\WhmcsRegistrar\Controllers\Hooks;

use OpenProvider\WhmcsRegistrar\src\Configuration;
use OpenProvider\WhmcsRegistrar\helpers\DB;

class ShoppingCartController
{
    public function checkoutOutput($vars)
    {
        GLOBAL $_LANG;

        $idnumbermod = Configuration::get('idnumbermod');

        if ($idnumbermod) {
            $data = [];
            $domainTlds = [];
            $domainsToMatch = ['es', 'pt', 'com.es', 'nom.es', 'edu.es', 'org.es'];

            foreach ($vars['cart']['domains'] as $domain) {
                $domainName = $domain['domain'];
                $tld = $this->getFullTld($domainName, $domainsToMatch);
                if (!$tld) {
                    continue; // skip if TLD not matched
                }

                $domainTlds[$domainName] = $tld;
                $fieldData = [];

                if (in_array($tld, ['es', 'pt', 'com.es', 'nom.es', 'edu.es', 'org.es'])) {
                    $f         = 0;
                    foreach ($domain['fields'] as $field) {
                        $f++;
                        switch ($f) {
                            case 1:
                                $fieldData['field'] = $field;
                                break;
                            case 2:
                                $fieldData['value'] = $field;
                                break;
                        }
                    }
                    if (!empty($fieldData['field']) && !empty($fieldData['value'])) {
                        $data[$domain['domain']] = [$fieldData];
                    }
                }
            }

            foreach ($data as $domain => $fieldsArray) {
                $tld = $domainTlds[$domain] ?? '';
                if (isset($fieldsArray[0]) && is_array($fieldsArray[0])) {
                    foreach ($fieldsArray as $fields) {
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
                }
            }

            $output = '<script type="text/javascript">$("#domainRegistrantInputFields").append(`' . addslashes($fieldDisplay) . '`)</script>';

            return $output;
        }
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

    private function getFullTld(string $domainName, array $domainsToMatch): ?string
    {
        foreach ($domainsToMatch as $tld) {
            if (str_ends_with($domainName, '.' . $tld)) {
                return $tld;
            }
        }
        return null;
    }
}
