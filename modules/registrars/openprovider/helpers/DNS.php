<?php

namespace OpenProvider\WhmcsRegistrar\helpers;

use OpenProvider\WhmcsRegistrar\src\Configuration;
use OpenProvider\WhmcsRegistrar\src\OpenProvider;
use OpenProvider\WhmcsRegistrar\src\RestClient;
use WHMCS\Database\Capsule;

class DNS
{
    /**
     * Get the DNS URL
     *
     * @param int $domain_id
     * @return string|null|false
     */
    public static function getDnsUrlOrFail($domain_id)
    {
        // Get the domain details
        $domain = Capsule::table('tbldomains')
            ->where('id', $domain_id)
            ->first();

        // Check if OpenProvider is the provider
        if ($domain->registrar != 'openprovider' || $domain->status != 'Active') {
            return false;
        }

        // Check if we are allowed to make a redirect.
        $useNew = (bool) \OpenProvider\WhmcsRegistrar\src\Configuration::getOrDefault('useNewDnsManagerFeature', true);

        if (!$useNew) {
            return false;
        }

        // Let's get the URL.
        try {
            [$username, $password] = self::getRegistrarCredentials();

            if ($username === '' || $password === '') {
                return null;
            }

            $client = new RestClient();
            $client->login($username, $password);

            // Create a domain token
            $url = $client->createDomainToken($domain->domain, 'openprovider');

            return $url;
        } catch (\Exception $e) {
            \logModuleCall('openprovider', 'Fetching generateSingleDomainTokenRequest', $domain->domain, null, $e->getMessage(), []);
            return false;
        }
    }

    /**
     * Fetch registrar credentials directly from WHMCS DB
     *
     * @return array [username, password]
     */
    private static function getRegistrarCredentials(): array
    {
        $rows = Capsule::table('tblregistrars')
            ->where('registrar', 'openprovider')
            ->whereIn('setting', ['Username', 'Password'])
            ->pluck('value', 'setting');

        $rawUsername = (string)($rows['Username'] ?? '');
        $rawPassword = (string)($rows['Password'] ?? '');

        $username = self::smartDecrypt($rawUsername);
        $password = self::smartDecrypt($rawPassword);

        return [$username, $password];
    }
    private static function smartDecrypt(string $value): string
    {
        if ($value === '') {
            return '';
        }

        $dec1 = $value;
        try {
            $tmp = \decrypt($value);
            if ($tmp !== '' && $tmp !== $value) {
                $dec1 = $tmp;
            }
        } catch (\Throwable $e) {
        }

        $looksEncoded = (bool)preg_match('/^[A-Za-z0-9+\/=]{16,}$/', $dec1);
        if ($looksEncoded) {
            try {
                $tmp2 = \decrypt($dec1);
                if ($tmp2 !== '' && $tmp2 !== $dec1) {
                    return $tmp2;
                }
            } catch (\Throwable $e) {
            }
        }

        return $dec1;
    }
}
