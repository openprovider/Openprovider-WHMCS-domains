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

            foreach ($vars['cart']['domains'] as $domain) {
                $domainName = $domain['domain'];
                $fields = $domain['fields'];
                $tld = $this->getFullTld($domainName);

                if (!$tld) {
                    continue; // skip if TLD not matched
                }

                $domainTlds[$domainName] = $tld;
                $fieldData = [];

                if (in_array($tld, ['es', 'pt', 'se', 'com.es', 'nom.es', 'edu.es', 'org.es'])) {
                    $f         = 0;
                    foreach ($fields as $field) {
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
                        $data[$domainName] = [$fieldData];
                    }
                } elseif ($tld === 'it') {
                    $itFieldsMap = [
                        7 => 'companyRegistrationNumber',
                        9 => 'socialSecurityNumber',
                    ];

                    $mappedFields = $this->mapFieldsByIndex($fields, $itFieldsMap);
                } elseif ($tld === 'fi') {
                    $fiFieldsMap = [
                        1 => 'companyRegistrationNumber',
                        2 => 'passportNumber',
                        3 => 'socialSecurityNumber',
                        4 => 'birthDate',
                    ];

                    $mappedFields = $this->mapFieldsByIndex($fields, $fiFieldsMap);
                }
                if (!empty($mappedFields)) {
                    $data[$domainName] = $mappedFields;
                }
            }

            foreach ($data as $domain => $fieldsArray) {
                $tld = $domainTlds[$domain] ?? '';
                if (isset($fieldsArray[0]) && is_array($fieldsArray[0])) {
                    foreach ($fieldsArray as $fields) {
                        $name = '';
                        switch ($fields['field']) {
                            case 'companyRegistrationNumber':
                                if (in_array($tld, ['es', 'com.es', 'nom.es', 'edu.es', 'org.es'])) {
                                    $name = $_LANG['esIdentificationCompany'];
                                } elseif ($tld === 'se') {
                                    $name = $_LANG['seIdentificationCompany'];
                                } elseif ($tld === 'it') {
                                    $name = $_LANG['itIdentificationCompany'];
                                } elseif ($tld === 'fi') {
                                    $name = $_LANG['fiIdentificationCompany'];
                                }
                                break;
                            case 'passportNumber':
                                if (in_array($tld, ['es', 'com.es', 'nom.es', 'edu.es', 'org.es'])) {
                                    $name = $_LANG['esIdentificationPassport'];
                                } elseif ($tld === 'fi') {
                                    $name = $_LANG['fiIdentificationPassport'];
                                }
                                break;
                            case 'vat':
                                $name = $_LANG['ptIdentificationVat'];
                                break;
                            case 'socialSecurityNumber':
                                if ($tld === 'pt') {
                                    $name = $_LANG['ptIdentificationSocialSecurityNumber'];
                                } elseif ($tld === 'se') {
                                    $name = $_LANG['seIdentificationSocialSecurityNumber'];
                                } elseif ($tld === 'it') {
                                    $name = $_LANG['itIdentificationSocialSecurityNumber'];
                                } elseif ($tld === 'fi') {
                                    $name = $_LANG['fiIdentificationSocialSecurityNumber'];
                                }
                                break;
                            case 'birthDate':
                                $name = $_LANG['fiIdentificationBirthDate'];
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
                    $fieldsArray = array_values($domain['fields']);
                    if (isset($fieldsArray[0]) && isset($fieldsArray[1])) {
                        $fieldData = [
                            'field' => $fieldsArray[0],
                            'value' => $fieldsArray[1],
                        ];

                        if (!empty($fieldData['value']) && !empty($fieldData['field']) && !empty($contactid)) {
                            DB::updateOrCreateContact($fieldData['value'], $contactid, $fieldData['field']);
                        }
                    }
                }
            }
        }
    }

    private function getFullTld(string $domainName): string
    {
        $multiTlds = ['com.es', 'nom.es', 'edu.es', 'org.es'];

        foreach ($multiTlds as $multiTld) {
            if (str_ends_with($domainName, '.' . $multiTld)) {
                return $multiTld;
            }
        }

        $tld = explode('.', $domainName)[1];
        return $tld;
    }

    private function mapFieldsByIndex(array $fields, array $map): array
    {
        $result = [];
        foreach ($map as $index => $fieldName) {
            if (!empty($fields[$index])) {
                $result[] = [
                    'field' => $fieldName,
                    'value' => $fields[$index],
                ];
            }
        }
        return $result;
    }
}
