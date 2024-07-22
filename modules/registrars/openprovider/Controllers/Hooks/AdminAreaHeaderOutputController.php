<?php

namespace OpenProvider\WhmcsRegistrar\Controllers\Hooks;

use WHMCS\Database\Capsule;

class AdminAreaHeaderOutputController
{
    public function output($vars)
    {       
        $return = '';
        if ($vars["pagetitle"] == 'TLD Import & Pricing Sync') {
            
            $isTestMode = false;
            $rows = Capsule::table('tblregistrars')->get();

            foreach ($rows as $row) {
                $registrar = $row->registrar;
                $setting = $row->setting;
                $value = decrypt($row->value); // Decrypt the value if needed

                if ($registrar == 'openprovider' && $setting == 'test_mode') {
                    if ($value == 'on') {
                        $isTestMode = true;
                    } else {
                        $isTestMode = false;
                    }
                    break;
                }
            }

            if($isTestMode){
                $return = '<div class="alert alert-danger global-admin-warning"> <i class="far fa-exclamation-triangle fa-fw"></i> <b>Prices in the Sandbox environment are for reference only and are not actual. Please re-run TLD Sync when switching to the production environment.</b></div>';
                return $return;
            }
            
            return $return;
        }

        return $return;
    }
}
