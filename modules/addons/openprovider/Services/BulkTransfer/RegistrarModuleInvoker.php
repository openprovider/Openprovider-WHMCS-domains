<?php

namespace OpenProvider\WhmcsDomainAddon\Services\BulkTransfer;

class RegistrarModuleInvoker
{
    public function buildModuleParams($domain, $client)
    {
        $domainObject = DomainLookupObject::fromDomain($domain->domain);
        $whoisContacts = $this->getWhoisContacts((int) $domain->id);
        $ownerContact = $whoisContacts['Owner'] ?? [];

        if (empty($ownerContact)) {
            throw new \RuntimeException('WHMCS LocalAPI returned no registrant WHOIS details.');
        }

        $contactDetails = [
            'Owner' => $this->mapWhoisContactToContactDetails(
                $ownerContact,
                $this->getContactDefaultLanguage($client, $ownerContact)
            ),
        ];

        foreach (['Admin', 'Tech', 'Billing'] as $role) {
            if (empty($whoisContacts[$role])) {
                continue;
            }

            $mappedContactDetails = $this->mapWhoisContactToContactDetails(
                $whoisContacts[$role],
                $this->getContactDefaultLanguage($client, $whoisContacts[$role])
            );
            if (!empty($mappedContactDetails)) {
                $contactDetails[$role] = $mappedContactDetails;
            }
        }

        return [
            'userid' => (int) $domain->userid,
            'domainid' => (int) $domain->id,
            'domain' => $domain->domain,
            'sld' => $domainObject->getSecondLevel(),
            'tld' => $domainObject->getTopLevel(),
            'regperiod' => max(1, (int) ($domain->registrationperiod ?: 1)),
            'dnsmanagement' => !empty($domain->dnsmanagement) ? 1 : 0,
            'emailforwarding' => !empty($domain->emailforwarding) ? 1 : 0,
            'idprotection' => !empty($domain->idprotection) ? 1 : 0,
            'contactdetails' => $contactDetails,
            'domainObj' => $domainObject,
            'original' => [
                'domainObj' => $domainObject,
            ],
        ];
    }

    protected function getContactDefaultLanguage($client, array $contact)
    {
        if (empty($client->language) || empty($contact)) {
            return null;
        }

        $clientContactDetails = $this->buildClientContactDetails($client);
        if (empty($clientContactDetails)) {
            return null;
        }

        $mappedContactDetails = $this->mapWhoisContactToContactDetails($contact);
        if (!$this->contactDetailsMatch($mappedContactDetails, $clientContactDetails)) {
            return null;
        }

        return (string) $client->language;
    }

    protected function buildClientContactDetails($client)
    {
        $addressParts = array_filter([
            isset($client->address1) ? trim((string) $client->address1) : null,
            isset($client->address2) ? trim((string) $client->address2) : null,
        ], function ($value) {
            return $value !== null && $value !== '';
        });

        $details = [
            'First Name' => isset($client->firstname) ? trim((string) $client->firstname) : null,
            'Last Name' => isset($client->lastname) ? trim((string) $client->lastname) : null,
            'Company Name' => isset($client->companyname) ? trim((string) $client->companyname) : null,
            'Email Address' => isset($client->email) ? trim((string) $client->email) : null,
            'Address' => empty($addressParts) ? null : implode(' ', $addressParts),
            'City' => isset($client->city) ? trim((string) $client->city) : null,
            'State' => isset($client->state) ? trim((string) $client->state) : null,
            'Zip Code' => isset($client->postcode) ? trim((string) $client->postcode) : null,
            'Country' => isset($client->country) ? trim((string) $client->country) : null,
            'Phone Number' => isset($client->phonenumber) ? trim((string) $client->phonenumber) : null,
        ];

        return array_filter($details, function ($value) {
            return $value !== null && $value !== '';
        });
    }

    protected function contactDetailsMatch(array $contactDetails, array $clientContactDetails)
    {
        $fields = [
            'First Name',
            'Last Name',
            'Company Name',
            'Email Address',
            'Address',
            'City',
            'State',
            'Zip Code',
            'Country',
            'Phone Number',
        ];

        foreach ($fields as $field) {
            $contactValue = $this->normalizeComparableContactValue($field, $contactDetails[$field] ?? null);
            $clientValue = $this->normalizeComparableContactValue($field, $clientContactDetails[$field] ?? null);

            if ($contactValue !== $clientValue) {
                return false;
            }
        }

        return true;
    }

    protected function normalizeComparableContactValue($field, $value)
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        switch ($field) {
            case 'Email Address':
                return strtolower($value);

            case 'Country':
                return strtoupper($value);

            case 'Phone Number':
                return preg_replace('/\D+/', '', $value);

            default:
                return strtolower(preg_replace('/\s+/', ' ', $value));
        }
    }

    public function unlockDomain(array $params)
    {
        $response = localAPI('DomainUpdateLockingStatus', [
            'domainid' => (int) $params['domainid'],
            'lockstatus' => false,
        ]);

        $this->assertSuccessfulResponse($response, 'Failed to unlock domain.');
    }

    public function getEppCode(array $params)
    {
        $response = localAPI('DomainRequestEPP', [
            'domainid' => (int) $params['domainid'],
        ]);
        $this->assertSuccessfulResponse($response, 'Failed to get EPP code.');

        if (!is_array($response) || empty($response['eppcode'])) {
            throw new \RuntimeException('WHMCS LocalAPI returned an empty EPP code.');
        }

        return html_entity_decode(trim((string) $response['eppcode']), ENT_QUOTES);
    }

    protected function assertSuccessfulResponse($response, $fallbackMessage)
    {
        if (is_array($response) && !empty($response['error'])) {
            throw new \RuntimeException((string) $response['error']);
        }

        if (is_array($response) && isset($response['result']) && strtolower((string) $response['result']) !== 'success') {
            $message = !empty($response['message']) ? $response['message'] : $fallbackMessage;
            throw new \RuntimeException((string) $message);
        }

        if (is_array($response) && isset($response['status']) && strtolower((string) $response['status']) !== 'success') {
            $message = !empty($response['message']) ? $response['message'] : $fallbackMessage;
            throw new \RuntimeException((string) $message);
        }
    }

    protected function getWhoisContacts($domainId)
    {
        $response = localAPI('DomainGetWhoisInfo', [
            'domainid' => (int) $domainId,
        ]);

        $this->assertSuccessfulResponse($response, 'Failed to get domain WHOIS information.');

        $roles = [];
        $ownerRawContact = $response['Owner'] ?? $response['Registrant'] ?? null;
        if (!empty($ownerRawContact)) {
            $decodedOwnerContact = $this->decodeWhoisContact($ownerRawContact);
            if (!empty($decodedOwnerContact)) {
                $roles['Owner'] = $decodedOwnerContact;
            }
        }

        foreach (['Admin', 'Tech', 'Billing'] as $role) {
            if (empty($response[$role])) {
                continue;
            }

            $decodedContact = $this->decodeWhoisContact($response[$role]);
            if (!empty($decodedContact)) {
                $roles[$role] = $decodedContact;
            }
        }

        return $roles;
    }

    protected function decodeWhoisContact($rawContact)
    {
        if (is_array($rawContact)) {
            return $this->normalizeWhoisContact($rawContact);
        }

        if (!is_string($rawContact) || trim($rawContact) === '') {
            return [];
        }

        $decodedContact = json_decode($rawContact, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decodedContact)) {
            return $this->normalizeWhoisContact($decodedContact);
        }

        return [];
    }

    protected function normalizeWhoisContact(array $contact)
    {
        $normalized = [];

        foreach ($contact as $key => $value) {
            $normalized[$this->normalizeWhoisKey($key)] = is_scalar($value) || $value === null
                ? trim((string) $value)
                : $value;
        }

        return $normalized;
    }

    protected function normalizeWhoisKey($key)
    {
        return preg_replace('/[^a-z0-9]/', '', strtolower((string) $key));
    }

    protected function mapWhoisContactToContactDetails(array $contact, $defaultLanguage = null)
    {
        [$firstName, $lastName] = $this->extractWhoisName($contact);

        $details = [
            'First Name' => $firstName,
            'Last Name' => $lastName,
            'Company Name' => $this->getWhoisValue($contact, ['organisationname', 'companyname', 'company', 'Company_Name']),
            'Email Address' => $this->getWhoisValue($contact, ['emailaddress', 'email', 'Email_Address']),
            'Address' => $this->buildWhoisAddress($contact),
            'City' => $this->getWhoisValue($contact, ['city', 'City']),
            'State' => $this->getWhoisValue($contact, ['fullstate', 'state', 'province', 'State']),
            'Zip Code' => $this->getWhoisValue($contact, ['postcode', 'zipcode', 'zip', 'Zip_Code']),
            'Country' => $this->getWhoisValue($contact, ['country', 'Country']),
            'Phone Number' => $this->normalizeWhoisPhoneNumber($contact),
            'Language' => $this->getWhoisValue($contact, ['language', 'locale']) ?: $defaultLanguage,
        ];

        return array_filter($details, function ($value) {
            return $value !== null && $value !== '';
        });
    }

    protected function extractWhoisName(array $contact)
    {
        $firstName = $this->getWhoisValue($contact, ['firstname', 'first', 'First_Name']);
        $lastName = $this->getWhoisValue($contact, ['lastname', 'last', 'Last_Name']);

        if ($firstName || $lastName) {
            return [$firstName, $lastName];
        }

        $fullName = $this->getWhoisValue($contact, ['name', 'fullname']);
        if (!$fullName) {
            return [null, null];
        }

        $nameParts = preg_split('/\s+/', trim($fullName), 2);

        return [
            $nameParts[0] ?? null,
            $nameParts[1] ?? null,
        ];
    }

    protected function buildWhoisAddress(array $contact)
    {
        $address = $this->getWhoisValue($contact, ['address', 'fulladdress', 'Address']);
        if ($address) {
            return $address;
        }

        $addressParts = array_filter([
            $this->getWhoisValue($contact, ['address1']),
            $this->getWhoisValue($contact, ['address2']),
            $this->getWhoisValue($contact, ['address3']),
        ], function ($value) {
            return $value !== null && $value !== '' && $value !== 'null';
        });

        return empty($addressParts) ? null : implode(' ', $addressParts);
    }

    protected function normalizeWhoisPhoneNumber(array $contact)
    {
        $phoneNumber = $this->getWhoisValue($contact, ['phone', 'telephone', 'telephonenumber', 'Phone_Number', 'phonenumber']);
        if (!$phoneNumber) {
            return null;
        }

        $phoneNumber = preg_replace('/\s+/', '', $phoneNumber);
        if (strpos($phoneNumber, '.') !== false) {
            return $phoneNumber;
        }

        $phoneCountryCode = $this->getWhoisValue($contact, ['telcountrycode', 'phonecountrycode']);
        if ($phoneCountryCode) {
            return '+' . ltrim($phoneCountryCode, '+') . '.' . ltrim($phoneNumber, '+');
        }

        $country = $this->getWhoisValue($contact, ['country']);
        if (!$country || strpos($phoneNumber, '+') !== 0) {
            return $phoneNumber;
        }

        $callingCode = $this->getCountryCallingCode($country);
        if (!$callingCode) {
            return $phoneNumber;
        }

        $internationalPrefix = '+' . $callingCode;
        if (strpos($phoneNumber, $internationalPrefix) !== 0) {
            return $phoneNumber;
        }

        return $internationalPrefix . '.' . substr($phoneNumber, strlen($internationalPrefix));
    }

    protected function getCountryCallingCode($country)
    {
        $countries = $this->getWhmcsCountries();

        return $countries[$country]['callingCode'] ?? null;
    }

    protected function getWhmcsCountries()
    {
        static $countries;

        if (is_array($countries)) {
            return $countries;
        }

        $rootDir = $this->getWhmcsRootDir();
        $countryResourcesPath = $rootDir . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR . 'country';
        $countries = [];

        $defaultCountriesPath = $countryResourcesPath . DIRECTORY_SEPARATOR . 'dist.countries.json';
        if (file_exists($defaultCountriesPath)) {
            $countries = json_decode(file_get_contents($defaultCountriesPath), true) ?: [];
        }

        $customCountriesPath = $countryResourcesPath . DIRECTORY_SEPARATOR . 'countries.json';
        if (file_exists($customCountriesPath)) {
            $customCountries = json_decode(file_get_contents($customCountriesPath), true) ?: [];
            $countries = array_merge($countries, $customCountries);
        }

        return $countries;
    }

    protected function getWhoisValue(array $contact, array $keys)
    {
        foreach ($keys as $key) {
            if (!array_key_exists($key, $contact)) {
                continue;
            }

            if ($contact[$key] === null || $contact[$key] === '') {
                continue;
            }

            return is_string($contact[$key]) ? trim($contact[$key]) : $contact[$key];
        }

        return null;
    }

    protected function getWhmcsRootDir()
    {
        if (defined('ROOTDIR')) {
            return ROOTDIR;
        }

        return realpath(__DIR__ . '/../../../../../');
    }
}
