<?php

namespace OpenProvider\WhmcsRegistrar\Controllers\Hooks;

use OpenProvider\WhmcsRegistrar\src\Configuration;
use OpenProvider\WhmcsRegistrar\helpers\DB;
use idna_convert;
use WHMCS\Database\Capsule;
use WHMCS\Config\Setting;

class ShoppingCartController
{
    private static ?array $inSldExtensions = null;
    private const IN_NEXUS_DECLARATION_INDEX = 0;

    // .RU / .xn--p1ai field indices
    private const RU_CONTACT_TYPE_INDEX                    = 8;  // Contact Type (display only)
    private const RU_REGISTRANT_RESIDENCY_INDEX            = 9;  // Registrant Residency (display only)
    private const RU_MOBILE_PHONE_COUNTRY_CODE_INDEX       = 10;  // all
    private const RU_MOBILE_PHONE_NUMBER_INDEX             = 11;  // all
    private const RU_POSTAL_ADDRESS_CITY_INDEX        = 12; // all
    private const RU_FIRST_NAME_CYRILLIC_INDEX        = 18; // individual + ru
    private const RU_LAST_NAME_CYRILLIC_INDEX         = 20; // individual + ru
    private const RU_COMPANY_NAME_CYRILLIC_INDEX      = 32; // company + ru
    private const RU_COMPANY_NAME_LATIN_INDEX         = 33; // company
    private const RU_LEGAL_ADDRESS_COUNTRY_CODE_INDEX = 34; // company
    private const RU_LEGAL_ADDRESS_POSTAL_CODE_INDEX  = 35; // company
    private const RU_LEGAL_ADDRESS_CITY_INDEX         = 36; // company

    private static array $itFieldsMap = [
        7 => 'companyRegistrationNumber',
        9 => 'socialSecurityNumber',
    ];

    private static array $fiFieldsMap = [
        1 => 'companyRegistrationNumber',
        2 => 'passportNumber',
        3 => 'socialSecurityNumber',
        4 => 'birthDate',
    ];

    public function checkoutOutput($vars)
    {
        global $_LANG;

        $idnumbermod = Configuration::get('idnumbermod');
        if ($idnumbermod) {
            $data = [];
            $domainTlds = [];
            $fieldDisplay = '';

            if (empty($vars['cart']['domains']) || !is_array($vars['cart']['domains'])) {
                return '';
            }

            foreach ($vars['cart']['domains'] as $domain) {
                $mappedFields = [];
                $domainName = $domain['domain'];
                $fields = $domain['fields'] ?? [];

                if (!is_array($fields)) {
                    continue;
                }
                
                $tld = $this->getFullTld($domainName);

                if (!$tld) {
                    continue;
                }

                $domainTlds[$domainName] = $tld;
                $fieldData = [];

                if (in_array($tld, ['es', 'pt', 'se', 'com.es', 'nom.es', 'edu.es', 'org.es'])) {
                    $f = 0;
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
                    $mappedFields = $this->mapFieldsByIndex($fields, self::$itFieldsMap);
                } elseif ($tld === 'fi') {
                    $mappedFields = $this->mapFieldsByIndex($fields, self::$fiFieldsMap);
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
                        $fieldId = htmlspecialchars($domain . "_" . $fields["field"], ENT_QUOTES, 'UTF-8');
                        $fieldName = htmlspecialchars($fields["field"], ENT_QUOTES, 'UTF-8');
                        $fieldValue = htmlspecialchars("[$domain] $name: " . $fields["value"], ENT_QUOTES, 'UTF-8');

                        $fieldDisplay .= "<div class='col-sm-12'><div class='form-group prepend-icon'><label class='field-icon' for='" . $fieldId . "'> <i class='fas id-card'></i></label><input required class='form-control' readonly id='" . $fieldId . "' type='text' name='" . $fieldName . "' value='" . $fieldValue . "' /> </div></div>";
                    }
                }
            }

            $output = '<script type="text/javascript">$("#domainRegistrantInputFields").append(' . json_encode($fieldDisplay) . ')</script>';

            return $output;
        }

        return ''; // Return empty string if no output
    }
    
    public function preCheckout($vars)
    {
        $inNexusError = $this->validateInNexusAtCheckout($vars);
        if ($inNexusError !== null) {
            return $inNexusError;
        }

        $ruError = $this->validateRuContactTypeAtCheckout($vars);
        if ($ruError !== null) {
            return $ruError;
        }

        $idnumbermod = Configuration::get('idnumbermod');

        if ($idnumbermod) {

            $contactid      = $vars['contact'];

            foreach ($vars['domains'] as $domain) {
                $domainName = $domain['domain'];
                $fields = $domain['fields'];
                $tld = $this->getFullTld($domainName);

                if (!$tld || empty($fields) || empty($contactid)) {
                    continue;
                }
                if (in_array($tld, ['es', 'pt', 'se', 'com.es', 'nom.es', 'edu.es', 'org.es'])) {
                    $fieldsArray = array_values($fields);
                    if (isset($fieldsArray[0]) && isset($fieldsArray[1])) {
                        $fieldData = [
                            'field' => $fieldsArray[0],
                            'value' => $fieldsArray[1],
                        ];

                        if (!empty($fieldData['value']) && !empty($fieldData['field']) && !empty($contactid)) {
                            DB::updateOrCreateContact($fieldData['value'], $contactid, $fieldData['field']);
                        }
                    }
                } elseif ($tld === 'it') {
                    $this->updateContactFields(self::$itFieldsMap, $fields, $contactid);
                } elseif ($tld === 'fi') {
                    $this->updateContactFields(self::$fiFieldsMap, $fields, $contactid);
                }
            }
        }
    }

    private function validateRuContactTypeAtCheckout(array $vars): ?array
    {
        $domains = $vars['domains'] ?? $_SESSION['cart']['domains'] ?? [];

        foreach ($domains as $domain) {
            $domainName = $domain['domain'] ?? '';
            if (!$this->isDomainRuTld($domainName)) {
                continue;
            }

            $fields      = $domain['fields'] ?? [];
            $contactType = $fields[self::RU_CONTACT_TYPE_INDEX]         ?? '';
            $residency   = $fields[self::RU_REGISTRANT_RESIDENCY_INDEX] ?? '';

            // Validate shared mandatory fields (all contact types, all residencies)
            $mobileCountryCode = trim((string) ($fields[self::RU_MOBILE_PHONE_COUNTRY_CODE_INDEX] ?? ''));
            $mobilePhoneNumber = trim((string) ($fields[self::RU_MOBILE_PHONE_NUMBER_INDEX] ?? ''));
            $postalCity        = trim((string) ($fields[self::RU_POSTAL_ADDRESS_CITY_INDEX] ?? ''));

            if ($mobileCountryCode === '' || $mobilePhoneNumber === '' || $postalCity === '') {
                $cartUrl = rtrim(Setting::getValue('SystemURL'), '/') . '/cart.php?a=confdomains';
                return [
                    'error' => 'To register ' . $domainName . ', Mobile Phone Country Code, Mobile Phone Number, and Postal Address City are required. '
                        . 'Please <a href="' . $cartUrl . '">go back to the domain configuration step</a> and fill in the required fields.',
                ];
            }

            // Individual + Russian resident: First and Last Name in Cyrillic are required
            if ($contactType === 'individual' && $residency === 'ru') {
                $firstNameCyrillic = trim((string) ($fields[self::RU_FIRST_NAME_CYRILLIC_INDEX] ?? ''));
                $lastNameCyrillic  = trim((string) ($fields[self::RU_LAST_NAME_CYRILLIC_INDEX]  ?? ''));

                if ($firstNameCyrillic === '' || $lastNameCyrillic === '') {
                    $cartUrl = rtrim(Setting::getValue('SystemURL'), '/') . '/cart.php?a=confdomains';
                    return [
                        'error' => 'To register ' . $domainName . ' as a Russian resident individual, First Name and Last Name in Cyrillic are required. '
                            . 'Please <a href="' . $cartUrl . '">go back to the domain configuration step</a> and fill in the required fields.',
                    ];
                }
            }

            // Company contacts: Latin name and legal address are required (all residencies)
            if ($contactType === 'company') {
                $companyNameLatin = trim((string) ($fields[self::RU_COMPANY_NAME_LATIN_INDEX]         ?? ''));
                $legalCountryCode = trim((string) ($fields[self::RU_LEGAL_ADDRESS_COUNTRY_CODE_INDEX] ?? ''));
                $legalPostalCode  = trim((string) ($fields[self::RU_LEGAL_ADDRESS_POSTAL_CODE_INDEX]  ?? ''));
                $legalCity        = trim((string) ($fields[self::RU_LEGAL_ADDRESS_CITY_INDEX]         ?? ''));

                if ($companyNameLatin === '' || $legalCountryCode === '' || $legalPostalCode === '' || $legalCity === '') {
                    $cartUrl = rtrim(Setting::getValue('SystemURL'), '/') . '/cart.php?a=confdomains';
                    return [
                        'error' => 'To register ' . $domainName . ' as a Company, Company Name (Latin), Legal Address Country Code, Postal Code, and City are required. '
                            . 'Please <a href="' . $cartUrl . '">go back to the domain configuration step</a> and complete the required Company fields.',
                    ];
                }

                // Company + Russian resident: Company Name in Cyrillic is additionally required
                if ($residency === 'ru') {
                    $companyNameCyrillic = trim((string) ($fields[self::RU_COMPANY_NAME_CYRILLIC_INDEX] ?? ''));

                    if ($companyNameCyrillic === '') {
                        $cartUrl = rtrim(Setting::getValue('SystemURL'), '/') . '/cart.php?a=confdomains';
                        return [
                            'error' => 'To register ' . $domainName . ' as a Russian resident company, Company Name in Cyrillic is also required. '
                                . 'Please <a href="' . $cartUrl . '">go back to the domain configuration step</a> and fill in the required fields.',
                        ];
                    }
                }
            }
        }

        return null;
    }

    private function isDomainRuTld(string $domainName): bool
    {
        return str_ends_with($domainName, '.ru') || str_ends_with($domainName, '.xn--p1ai');
    }

    private function validateInNexusAtCheckout(array $vars): ?array
    {
        $domains = $vars['domains'] ?? $_SESSION['cart']['domains'] ?? [];

        foreach ($domains as $domain) {
            $domainName = $domain['domain'] ?? '';
            if (!$this->isDomainInSld($domainName)) {
                continue;
            }

            $country = $this->getRegistrantCountryForCheckout($vars);

            if ($country === 'IN') {
                continue;
            }

            $fields = $domain['fields'] ?? [];
            $attestation = $fields[self::IN_NEXUS_DECLARATION_INDEX] ?? '';

            if ($attestation !== 'on') {
                $cartUrl = rtrim(Setting::getValue('SystemURL'), '/') . '/cart.php?a=confdomains';
                return [
                    'error' => 'To register ' . $domainName . ', non-Indian registrants must confirm the .IN nexus declaration. '
                        . 'Please <a href="' . $cartUrl . '">go back to the domain configuration step</a> and check "I agree and confirm".',
                ];
            }
        }

        return null;
    }

    private function isDomainInSld(string $domainName): bool
    {
        foreach (self::getInSldExtensions() as $ext) {
            if (str_ends_with($domainName, '.' . $ext)) {
                return true;
            }
        }
        return false;
    }

    private static function getInSldExtensions(): array
    {
        if (self::$inSldExtensions !== null) {
            return self::$inSldExtensions;
        }
        $inSlds = [];
        require dirname(__DIR__, 2) . '/configuration/additionalfields.php';
        self::$inSldExtensions = array_values(array_map(
            static fn(string $sld): string => ltrim(strtolower($sld), '.'),
            (array) $inSlds
        ));
        return self::$inSldExtensions;
    }

    private function getRegistrantCountryForCheckout(array $vars): string
    {
        $contact = $vars['contact'] ?? '';

        if ($contact === 'addingnew') {
            return strtoupper((string) ($vars['domaincontactcountry'] ?? ''));
        }

        $contactId = (int) $contact;
        if ($contactId > 0) {
            $country = Capsule::table('tblcontacts')
                ->where('id', $contactId)
                ->value('country');
            if (!empty($country)) {
                return strtoupper((string) $country);
            }
        }

        return strtoupper((string) ($vars['country'] ?? ''));
    }

    public function injectDomainConfigFieldFilters($vars): string
    {
        $filename = $vars['filename'] ?? '';
        $action   = $_GET['a'] ?? '';

        if ($filename !== 'cart' || !in_array($action, ['confdomains'], true)) {
            return '';
        }

        $js = <<<'JS'
(function ($) {
    function initRuFieldVisibility() {

        // One Contact Type select exists per .ru domain in the cart.
        $('select').filter(function () {
            var vals = $(this).find('option').map(function () { return $(this).val(); }).get();
            return vals.indexOf('individual') !== -1 && vals.indexOf('company') !== -1;
        }).each(function () {
            var $ct  = $(this);
            var $form = $ct.closest('form');

            // Domain index N from name="domainfield[N][...]"
            var m = ($ct.attr('name') || '').match(/^domainfield\[(\d+)\]/);
            if (!m) { return; }
            var n = m[1];

            // Residency select for the same domain N
            var $res = $form.find('select').filter(function () {
                var nm = $(this).attr('name') || '';
                if (nm.indexOf('domainfield[' + n + '][') !== 0) { return false; }
                var vals = $(this).find('option').map(function () { return $(this).val(); }).get();
                return vals.indexOf('ru') !== -1 && vals.indexOf('non-ru') !== -1;
            }).first();

            if (!$res.length) { return; }

            // Pre-select only the .form-group.row elements that belong to domain N.
            // jQuery's [name^=value] (starts-with) attribute selector scopes this correctly.
            var $rows = $form.find('.form-group.row').filter(function () {
                return $(this).find('[name^="domainfield[' + n + ']["]').length > 0;
            });

            function apply() {
                var ct  = $ct.val();   // 'individual' | 'company'
                var res = $res.val();  // 'ru' | 'non-ru'

                $rows.each(function () {
                    var $row  = $(this);
                    var label = $row.find('.col-sm-4').first().text().trim();

                    var isInd = label.indexOf('(Individual') !== -1;
                    var isCo  = label.indexOf('(Company')    !== -1;

                    // Common fields — always visible
                    if (!isInd && !isCo) { $row.show(); return; }

                    // Contact type gate
                    if (isInd && ct !== 'individual') { $row.hide(); return; }
                    if (isCo  && ct !== 'company')    { $row.hide(); return; }

                    // Residency gate (fields marked "– Russian)")
                    if (label.indexOf('– Russian)') !== -1 && res !== 'ru') {
                        $row.hide(); return;
                    }

                    $row.show();
                });
            }

            $ct.on('change', apply);
            $res.on('change', apply);
            apply();
        });
    }

    $(document).ready(initRuFieldVisibility);
})(jQuery);
JS;

        return '<script type="text/javascript">' . $js . '</script>';
    }

    public function hideIdnScriptForNonIdnDomains($vars)
    {
        if (
            ($vars['filename'] ?? '') !== 'cart' ||
            ($vars['action'] ?? '') !== 'confdomains' ||
            ($vars['templatefile'] ?? '') !== 'configuredomains'
        ) {
            return [];
        }
        
        if (empty($vars['domains']) || !is_array($vars['domains'])) {
            return [];
        }

        $idn = new idna_convert();
        $updatedDomains = $vars['domains'];

        foreach ($updatedDomains as $i => $domainData) {
    
            if (empty($domainData['fields']) || empty($domainData['domain'])) {
                continue;
            }

            $domainName = $domainData['domain'];  

            // Detect IDN 
            $encoded = $idn->encode($domainName);

            $isNonIdn = ($domainName === $encoded) && (strpos($domainName, 'xn--') === false);

            if (!$isNonIdn) {
                continue;
            }

            foreach ($domainData['fields'] as $fieldName => $html) {
                if (stripos($fieldName, 'Internationalized domain name Script') !== false) {
                    unset($updatedDomains[$i]['fields'][$fieldName]);
                }
            }
        }

        return ['domains' => $updatedDomains];
    }

    private function getFullTld(string $domainName): string
    {
        $multiTlds = ['com.es', 'nom.es', 'edu.es', 'org.es'];

        foreach ($multiTlds as $multiTld) {
            if (str_ends_with($domainName, '.' . $multiTld)) {
                return $multiTld;
            }
        }

        $labels = array_values(array_filter(explode('.', $domainName), static function ($label) {
            return $label !== '';
        }));

        if (empty($labels)) {
            return '';
        }

        return (string) end($labels);
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

    private function updateContactFields(array $map, array $fields, $contactId): void
    {
        foreach ($map as $index => $fieldName) {
            if (!empty($fields[$index])) {
                $fieldData = [
                    'field' => $fieldName,
                    'value' => $fields[$index],
                ];

                DB::updateOrCreateContact($fieldData['value'], $contactId, $fieldData['field']);
            }
        }
    }
}