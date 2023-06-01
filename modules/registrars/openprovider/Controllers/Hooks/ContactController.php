<?php

namespace OpenProvider\WhmcsRegistrar\Controllers\Hooks;

use OpenProvider\WhmcsRegistrar\helpers\DB;
use OpenProvider\WhmcsRegistrar\src\Configuration;

class ContactController
{
    public function add($vars)
    {
        $idnumbermod = Configuration::get('idnumbermod');

        if ($idnumbermod) {

            $idtype = $_POST['id_type'];
            $cord   = $_POST['cord'];

            $this->queueContactUpdate($idtype, $cord);
        }
    }

    public function delete($vars)
    {
        $idnumbermod = Configuration::get('idnumbermod');

        if ($idnumbermod) {
            DB::verifyContactstables();
        }
    }

    public function edit($vars)
    {
        $idnumbermod = Configuration::get('idnumbermod');

        if ($idnumbermod) {
            DB::verifyContactstables();

            $cord      = $_POST['cord'];
            $contactid = $_POST['contactid'];
            $id_type   = $_POST['id_type'];

            DB::updateOrCreateContact($cord, $contactid, $id_type);
        }
    }

    private function queueContactUpdate($idtype , $cord , $contactid = null)
    {
        $_SESSION['Contact_Pending_Update'] = true;
        $_SESSION['id_type']                = $idtype;
        $_SESSION['cord']                   = $cord;
    }
}
