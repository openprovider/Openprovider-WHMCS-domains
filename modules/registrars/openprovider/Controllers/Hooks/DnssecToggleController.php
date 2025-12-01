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
            $tld_enabled = $this->apiHelper->supportsDnssec($tld);
        } catch (\Exception $e) {
            $tld_enabled = false;
        }

        $admin_enabled = $row && (int)($row->dnssecmanagement ?? 0) === 1;

        $enabled = $tld_enabled && $admin_enabled;

        $html = '
            <div style="display:flex;align-items:center;gap:10px;">
                <label style="display:inline-flex;align-items:center;gap:8px;">
                    <input type="checkbox" name="op_dnssec_management" value="1" '.($enabled ? 'checked' : '').' />
                    <span style="font-weight: normal;">'.'Check to Enable'.'</span>
                </label>
            </div>';

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

        unset($_SESSION['op_dnssec_popup']);

        $this->ensureDnssecColumn();

        $row = Capsule::table('tbldomains')
            ->select('id','domain','dnssecmanagement')
            ->where('id', $domainId)
            ->first();

        if (!$row) {
            return;
        }

        $current = (int)($row->dnssecmanagement ?? 0); 
        $checkEnable = isset($_REQUEST['op_dnssec_management']) && $_REQUEST['op_dnssec_management'] == '1';
        $wantsEnable = $checkEnable ? 1 : 0; 
        $tld = $this->extractTldFromFqdn((string)$row->domain);
        $allowed = false;
        try {
            $allowed = $this->apiHelper->supportsDnssec($tld);
        } catch (\Exception $e) {
            $allowed = false;
        }

        if ($wantsEnable === $current) {
            if ($wantsEnable === 1 && !$allowed) {
                $this->updateDnssecFlag((int) $row->id, 0);
                $this->setDnssecPopup(
                    (int) $row->id,
                    'warning',
                    'not_allowed',
                    'DNSSEC management cannot be enabled for the domain because the TLD does not support DNSSEC.'
                );
                return;
            }
            unset($_SESSION['op_dnssec_popup']);
            return;
        }

        if (!$allowed && $wantsEnable === 0 && $current === 1) {
            $this->updateDnssecFlag((int) $row->id, 0);
            return;
        }

        if ($wantsEnable === 0) {
            $this->updateDnssecFlag((int) $row->id, 0);
            $this->setDnssecPopup(
                (int) $row->id,
                'success',
                'disabled',
                'DNSSEC management has been disabled for this domain.'
            );
            return;
        }

        if ($allowed) {
            $this->updateDnssecFlag((int) $row->id, 1);
            $this->setDnssecPopup(
                (int) $row->id,
                'success',
                'enabled',
                'DNSSEC management has been successfully enabled for the domain. The client can now manage DNSSEC from the client area.'
            );
        } else {
            $this->updateDnssecFlag((int) $row->id, 0);
            $this->setDnssecPopup(
                (int) $row->id,
                'warning',
                'not_allowed',
                'DNSSEC management cannot be enabled for the domain because the TLD does not support DNSSEC.'
            );
        }
    }

    public function footer($vars)
    {
        if (empty($_SESSION['op_dnssec_popup'])) {
            return '';
        }

        $filename = (string)($vars['filename'] ?? '');
        $domainId = isset($_GET['id']) ? (int)$_GET['id'] : (isset($_GET['domainid']) ? (int)$_GET['domainid'] : 0);

        if ($filename !== 'clientsdomains' || $domainId <= 0) {
            unset($_SESSION['op_dnssec_popup']);
            return '';
        }

        $sess = $_SESSION['op_dnssec_popup'];
        $sessDomainId = (int)($sess['domainid'] ?? 0);
        if ($sessDomainId !== $domainId) {
            unset($_SESSION['op_dnssec_popup']);
            return '';
        }

        $ts  = (int)($sess['ts'] ?? 0);
        if ($ts <= 0 || (time() - $ts) > 30) {
            unset($_SESSION['op_dnssec_popup']);
            return '';
        }

        $type   = (string)($sess['type'] ?? 'success');       // success | warning
        $status = (string)($sess['status'] ?? 'enabled');     // enabled | disabled | not_allowed
        $msg    = (string)($sess['message'] ?? '');

        switch ($status) {
            case 'disabled':
                $title = 'DNSSEC Management: Disabled';
                break;
            case 'not_allowed':
                $title = 'DNSSEC Management: Not Allowed';
                break;
            case 'enabled':
            default:
                $title = 'DNSSEC Management: Enabled';
                break;
        }

        $palette = [
            'success' => ['bg' => '#dff0d8', 'fg' => '#3c763d', 'btn' => 'btn-success'],
            'warning' => ['bg' => '#fcf8e3', 'fg' => '#8a6d3b', 'btn' => 'btn-warning'],
        ];
        $colors  = $palette[$type] ?? $palette['success'];

        $escTitle = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');
        $escMsg   = htmlspecialchars($msg,   ENT_QUOTES, 'UTF-8');
        $bg       = $colors['bg'];
        $fg       = $colors['fg'];
        $btnClass = $colors['btn'];

        unset($_SESSION['op_dnssec_popup']); 
        $jsonMsg = $this->jsonInline($msg);

        return <<<HTML
        <style id="op-dnssec-modal-css">
        #opDnssecModal .modal-header{
            background: {$bg} !important;
            color: {$fg} !important;
            border-bottom: none !important;
        }
        #opDnssecModal .modal-title{
            color: {$fg} !important;
            font-weight: 600;
        }
        #opDnssecModal .modal-body p{
            margin: 0;
        }
        #opDnssecModal .modal-footer{
            border-top: none !important;
        }
        </style>

        <div class="modal fade" id="opDnssecModal" tabindex="-1" role="dialog" aria-labelledby="opDnssecModalLabel">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span>&times;</span></button>
                <h4 class="modal-title" id="opDnssecModalLabel">{$escTitle}</h4>
            </div>
            <div class="modal-body">
                <p>{$escMsg}</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn {$btnClass}" data-dismiss="modal">OK</button>
            </div>
            </div>
        </div>
        </div>
        <script>
        (function () {
        if (window.jQuery && jQuery.fn.modal) {
            jQuery(function () { jQuery('#opDnssecModal').modal('show'); });
        } else {
            try { alert({$jsonMsg}); } catch (e) {}
        }
        })();
        </script>
        HTML;
    }

    private function jsonInline($str)
    {
        return json_encode((string)$str, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP|JSON_HEX_QUOT);
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

    private function updateDnssecFlag(int $domainId, int $value): void
    {
        try {
            Capsule::table('tbldomains')
                ->where('id', $domainId)
                ->update(['dnssecmanagement' => $value]);
        } catch (\Exception $e) {}
    }

    private function setDnssecPopup(
        int $domainId,
        string $type,
        string $status,
        string $message
    ): void {
        $_SESSION['op_dnssec_popup'] = [
            'domainid' => $domainId,
            'type'     => $type,
            'status'   => $status,
            'message'  => $message,
            'ts'       => time(),
        ];
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
