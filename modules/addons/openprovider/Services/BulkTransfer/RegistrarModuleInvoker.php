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

        $this->assertOwnerOnlyWhoisContacts($whoisContacts);

        $adminContact = !empty($whoisContacts['Admin']) ? $whoisContacts['Admin'] : $ownerContact;
        $techContact = !empty($whoisContacts['Tech']) ? $whoisContacts['Tech'] : $adminContact;
        $billingContact = !empty($whoisContacts['Billing']) ? $whoisContacts['Billing'] : $adminContact;
        $defaultLanguage = !empty($client->language) ? (string) $client->language : null;

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
            'contactdetails' => array_filter([
                'Owner' => $this->mapWhoisContactToContactDetails($ownerContact, $defaultLanguage),
                'Admin' => $this->mapWhoisContactToContactDetails($adminContact, $defaultLanguage),
                'Tech' => $this->mapWhoisContactToContactDetails($techContact, $defaultLanguage),
                'Billing' => $this->mapWhoisContactToContactDetails($billingContact, $defaultLanguage),
            ]),
            'domainObj' => $domainObject,
            'original' => [
                'domainObj' => $domainObject,
            ],
        ];
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
    }

    protected function getWhoisContacts($domainId)
    {
        $dummyContact = [
            'firstname' => 'Dummy',
            'lastname' => 'Contact',
            'companyname' => 'Dummy Company',
            'emailaddress' => 'dummy@example.com',
            'address' => '123 Dummy Street',
            'city' => 'Dummy City',
            'state' => 'Dummy State',
            'zipcode' => '12345',
            'country' => 'US',
            'phone' => '+1.5551234567',
            'language' => 'en',
        ];

        return [
            'Owner' => $dummyContact,
            'Admin' => $dummyContact,
            'Tech' => $dummyContact,
            'Billing' => $dummyContact,
        ];
    }

    protected function mapWhoisContactToContactDetails(array $contact, $defaultLanguage = null)
    {
        $details = [
            'First Name' => $contact['firstname'] ?? null,
            'Last Name' => $contact['lastname'] ?? null,
            'Company Name' => $contact['companyname'] ?? null,
            'Email Address' => $contact['emailaddress'] ?? null,
            'Address' => $contact['address'] ?? null,
            'City' => $contact['city'] ?? null,
            'State' => $contact['state'] ?? null,
            'Zip Code' => $contact['zipcode'] ?? null,
            'Country' => $contact['country'] ?? null,
            'Phone Number' => $contact['phone'] ?? null,
            'Language' => $contact['language'] ?? ($contact['locale'] ?? $defaultLanguage),
        ];

        return array_filter($details, function ($value) {
            return $value !== null && $value !== '';
        });
    }

    protected function assertOwnerOnlyWhoisContacts(array $whoisContacts)
    {

        foreach (['Admin', 'Tech', 'Billing'] as $role) {
            $roleContact = $this->normalizeWhoisContactForComparison($whoisContacts[$role] ?? []);

            if (empty($roleContact)) {
                continue;
            }
            throw new \RuntimeException(
                'Bulk transfer is currently available only for owner-only WHOIS contact data. This domain has multiple contact types and is not available yet.'
            );
        }
    }

    protected function normalizeWhoisContactForComparison(array $contact)
    {
        $normalized = [];

        foreach ($contact as $key => $value) {
            $normalizedKey = strtolower(trim((string) $key));
            $normalizedValue = is_string($value) ? trim($value) : $value;

            if ($normalizedValue === null || $normalizedValue === '') {
                continue;
            }

            $normalized[$normalizedKey] = $normalizedValue;
        }

        ksort($normalized);

        return $normalized;
    }
}
