<?php

namespace OpenProvider\WhmcsRegistrar\helpers;

use WHMCS\TransientData;

/**
 * Tracks domain contact roles whose new handle was accepted by Openprovider as
 * a request (status REQ) and still has to be applied by the registry.
 *
 * Stored in WHMCS transient data instead of the PHP session: WHMCS closes the
 * session before calling the registrar module, so session writes made while
 * saving contact details are lost.
 */
class ContactChangePending
{
    private const KEY_PREFIX = 'op_contact_change_pending_';

    // Stop reporting a change as pending after this many seconds, even if the
    // registry has not applied it (e.g. the request was rejected).
    private const TTL = 1800;

    /**
     * @param int $domainId
     * @param array $handles Requested handle per role, e.g. ['adminHandle' => 'PR123456-LK']
     */
    public static function set(int $domainId, array $handles): void
    {
        if (empty($handles)) {
            self::clear($domainId);
            return;
        }

        TransientData::getInstance()->store(self::KEY_PREFIX . $domainId, json_encode($handles), self::TTL);
    }

    public static function get(int $domainId): array
    {
        $handles = json_decode((string) TransientData::getInstance()->retrieve(self::KEY_PREFIX . $domainId), true);

        return is_array($handles) ? $handles : [];
    }

    public static function clear(int $domainId): void
    {
        TransientData::getInstance()->delete(self::KEY_PREFIX . $domainId);
    }

    /**
     * Drop the roles the domain already uses the requested handle for.
     *
     * @param int $domainId
     * @param array $domainOp Domain data from Openprovider
     * @return array Roles that are still pending
     */
    public static function refresh(int $domainId, array $domainOp): array
    {
        $pending = self::get($domainId);
        if (empty($pending)) {
            return [];
        }

        $stillPending = array_filter($pending, function ($handle, $key) use ($domainOp) {
            return ($domainOp[$key] ?? null) !== $handle;
        }, ARRAY_FILTER_USE_BOTH);

        if (count($stillPending) !== count($pending)) {
            self::set($domainId, $stillPending);
        }

        return $stillPending;
    }
}
