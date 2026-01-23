<?php

namespace OpenProvider\WhmcsRegistrar\helpers;

use WHMCS\Database\Capsule;

class DnssecManagement
{
    public const EXTRA_KEY = 'openprovider_dnssecmanagement';
    public const DEFAULT_DNSSEC_MGMT = 1;

    public static function getFlag(int $domainId): int
    {
        try {
            $val = Capsule::table('tbldomains_extra')
                ->where('domain_id', $domainId)
                ->where('name', self::EXTRA_KEY)
                ->value('value');

            // No row = default behavior
            if ($val === null) {
                return self::DEFAULT_DNSSEC_MGMT;
            }

            return ((string)$val === '1') ? 1 : 0;
        } catch (\Exception $e) {
            return self::DEFAULT_DNSSEC_MGMT;
        }
    }

    public static function setFlag(int $domainId, int $value): void
    {
        $value = ($value === 1) ? '1' : '0';
        $now   = date('Y-m-d H:i:s');

        try {
            $query = Capsule::table('tbldomains_extra')
                ->where('domain_id', $domainId)
                ->where('name', self::EXTRA_KEY);

            if ($query->exists()) {
                $query->update([
                    'value'      => $value,
                    'updated_at' => $now,
                ]);
            } else {
                Capsule::table('tbldomains_extra')->insert([
                    'domain_id'  => $domainId,
                    'name'       => self::EXTRA_KEY,
                    'value'      => $value,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        } catch (\Exception $e) {}
    }
}
