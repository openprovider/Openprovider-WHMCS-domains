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
            $data = [];
            $domainTlds = []; // to store the full TLD of each domain

            // Supported TLDs from additionalfields.php
            $domainsToMatch = [
                'es',
                'pt',
                'se',
                'fi',
                'it',
                'com.es',
                'nom.es',
                'edu.es',
                'org.es' // Add other known multi-level TLDs
            ];

            // Helper to get full TLD
            $getFullTld = function ($domainName) use ($domainsToMatch) {
                foreach ($domainsToMatch as $tld) {
                    if (str_ends_with($domainName, '.' . $tld)) {
                        return $tld;
                    }
                }
                return null;
            };
            foreach ($vars['cart']['domains'] as $domain) {
                $domainName = $domain['domain'];
                $tld = $getFullTld($domainName);
                if (!$tld) {
                    continue; // skip if TLD not matched
                }
                // $tld       = explode('.', $domain['domain']);
                $domainTlds[$domainName] = $tld;
                $fieldData = [];

                if (in_array($tld, ['es', 'pt', 'se', 'com.es', 'nom.es', 'edu.es', 'org.es'])) {
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
                        $data[$domain['domain']] = [$fieldData]; // wrapped in array ✅
                    }
                } elseif ($tld === 'fi') {
                    // Mapping based on expected index positions
                    $fiFieldsMap = [
                        1 => 'companyRegistrationNumber',
                        2 => 'passportNumber',
                        3 => 'socialSecurityNumber',
                        4 => 'birthDate',
                    ];

                    $mappedFields = [];
                    foreach ($fiFieldsMap as $index => $name) {
                        if (!empty($domain['fields'][$index])) {
                            $mappedFields[] = [
                                'field' => $name,
                                'value' => $domain['fields'][$index],
                            ];
                        }
                    }

                    if (!empty($mappedFields)) {
                        $data[$domain['domain']] = $mappedFields;
                    }
                } elseif ($tld === 'it') {
                    $itFieldsMap = [
                        7 => 'companyRegistrationNumber',
                        9 => 'socialSecurityNumber',
                    ];

                    $mappedFields = [];
                    foreach ($itFieldsMap as $index => $name) {
                        if (!empty($domain['fields'][$index])) {
                            $mappedFields[] = [
                                'field' => $name,
                                'value' => $domain['fields'][$index],
                            ];
                        }
                    }

                    if (!empty($mappedFields)) {
                        $data[$domain['domain']] = $mappedFields;
                    }
                }
            }
            logModuleCall('TLD_Parser', 'ParseData', $vars, $data, null, null);

            foreach ($data as $domain => $fields) {
                $tld = $domainTlds[$domain] ?? '';
                if (isset($fields[0]) && is_array($fields[0])) {
                    foreach ($fields as $fieldItem) {
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
                                if ($tld === 'es') {
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

                        $fieldDisplay .= "<div class='col-sm-12'><div class='form-group prepend-icon'>
                            <label class='field-icon' for='" . $domain . "_" . $fieldItem["field"] . "'> 
                                <i class='fas id-card'></i></label>
                            <input required class='form-control' readonly id='" . $domain . "_" . $fieldItem["field"] . "' type='text' name='" . $fieldItem["field"] . "' value='[$domain] $name:  " . $fieldItem["value"] . "' /> 
                        </div></div>";
                    }
                }
            }
            // $output = '<script type="text/javascript">$("#domainRegistrantInputFields").append("' . $fieldDisplay . '")</script>';
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
                            $field == 'socialSecurityNumber' ||
                            $field == 'birthDate'
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
