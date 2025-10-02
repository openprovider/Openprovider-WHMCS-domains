<?php

namespace OpenProvider\WhmcsRegistrar\Controllers\Hooks;

use OpenProvider\API\ApiHelper;
use WHMCS\Database\Capsule;

/**
 * Class DnssecToggleController
 * OpenProvider Registrar module
 *
 * @copyright Copyright (c) Openprovider 2025
 */

class DnssecToggleController
{
    /**
     * @var ApiHelper
     */
    private $apiHelper;

    public function __construct(ApiHelper $apiHelper)
    {
        $this->apiHelper = $apiHelper;
    }

    /**
     * Output handler: show checkbox if supported and enabled
     * @return array 
     */
    public function output($vars)
    {
        $domainId = (int)($vars['domainid'] ?? $vars['id'] ?? 0);
        if ($domainId <= 0) {
            return [];
        }

        $this->ensureDnssecColumn();

        $row = Capsule::table('tbldomains')
            ->select('id','domain','dnssecmanagement')
            ->where('id', $domainId)
            ->first();

        $tld = $this->extractTldFromFqdn((string)$row->domain);
        
        $tld_enabled = false;
        try {
            $tld_enabled   =  $this->apiHelper->supportsDnssec($tld);
        } catch (\Exception $e) {
            $tld_enabled = false;
        }

        $admin_enabled = $row && (int)($row->dnssecmanagement ?? 0) === 1;

        $enabled = $tld_enabled && $admin_enabled;

        // Domain-scoped flash: show only once
        $flashMsg = '';
        $flashClass = '';
        if (!empty($_SESSION['op_dnssec_flash'])
            && isset($_SESSION['op_dnssec_flash']['domainid'])
            && (int)$_SESSION['op_dnssec_flash']['domainid'] === $domainId) {
            $flashMsg   = (string)($_SESSION['op_dnssec_flash']['message'] ?? '');
            $flashClass = (string)($_SESSION['op_dnssec_flash']['class'] ?? 'text-muted');
            unset($_SESSION['op_dnssec_flash']); 
        }

        $html = '
            <div style="display:flex;align-items:center;gap:10px;">
                <label style="display:inline-flex;align-items:center;gap:8px;">
                    <input type="checkbox" name="op_dnssec_management" value="1" '.($enabled ? 'checked' : '').' />
                    <span style="font-weight: normal;">'.'Check to Enable'.'</span>
                </label>
            </div>'
            . ($flashMsg !== ''
                ? '<div class="'.htmlspecialchars($flashClass).'" style="margin-top:6px;">'
                    . htmlspecialchars($flashMsg) .
                  '</div>'
                : '');

        return [
            'DNSSEC Management' => $html,
        ];
    }

    
    public function save($vars)
    {
        $domainId = (int)($vars['domainid'] ?? $vars['id'] ?? 0);
        if ($domainId <= 0) {
            return;
        }

        $this->ensureDnssecColumn();

        $row = Capsule::table('tbldomains')
            ->select('id','domain','dnssecmanagement')
            ->where('id', $domainId)
            ->first();

        if (!$row) {
            return;
        }

        $wantsEnable = isset($_REQUEST['op_dnssec_management']) && $_REQUEST['op_dnssec_management'] == '1';

        if (!$wantsEnable) {
            try {
                Capsule::table('tbldomains')->where('id', $row->id)->update(['dnssecmanagement' => 0]);
            } catch (\Exception $e) {}
            $_SESSION['op_dnssec_flash'] = [
                'domainid' => (int)$row->id,
                'class'    => 'text-success',
                'message'  => 'DNSSEC management has been disabled for this domain.',
            ];
            return;
        }

        $tld = $this->extractTldFromFqdn((string)$row->domain);
        $allowed = false;
        try {
            $allowed   =  $this->apiHelper->supportsDnssec($tld);
        } catch (\Exception $e) {
            $allowed = false;
        }

        if ($allowed) {
            try {
                Capsule::table('tbldomains')->where('id', $row->id)->update(['dnssecmanagement' => 1]);
            } catch (\Exception $e) {}
            $_SESSION['op_dnssec_flash'] = [
                'domainid' => (int)$row->id,
                'class'    => 'text-success',
                'message'  => 'DNSSEC management has been successfully enabled for the domain. The client can now manage DNSSEC from the client area.',
            ];
        } else {
            try {
                Capsule::table('tbldomains')->where('id', $row->id)->update(['dnssecmanagement' => 0]);
            } catch (\Exception $e) {}
            $_SESSION['op_dnssec_flash'] = [
                'domainid' => (int)$row->id,
                'class'    => 'text-warning',
                'message'  => 'DNSSEC management cannot be enabled for the domain because the TLD does not support DNSSEC.',
            ];
        }
    }

    private function ensureDnssecColumn(): void
    {
        $table = 'tbldomains';
        $col   = 'dnssecmanagement';

        try {
            if (!Capsule::schema()->hasTable($table) || Capsule::schema()->hasColumn($table, $col)) {
                return;
            }
            Capsule::schema()->table($table, function ($t) use ($col) {
                $t->tinyInteger($col)->unsigned()->default(1);
            });
        } catch (\Exception $e) {}
    }


    private function extractTldFromFqdn(string $fqdn): string
    {
        $labels = array_values(array_filter(explode('.', strtolower($fqdn))));
        if (count($labels) <= 1) {
            return '';
        }
        array_shift($labels); 
        return implode('.', $labels); 
    }
}
