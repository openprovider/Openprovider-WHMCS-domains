<?php


namespace OpenProvider\WhmcsRegistrar\helpers;

use WHMCS\Database\Capsule;

class DB
{
    public static function checkTableExist($tableName): bool
    {
        try {
            return Capsule::schema()->hasTable($tableName);
        } catch(\Exception $e) {
            return false;
        }
    }
}