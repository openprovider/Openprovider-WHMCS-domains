<?php


namespace OpenProvider\WhmcsRegistrar\helpers;

use OpenProvider\WhmcsRegistrar\enums\DatabaseTable;
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

    public static function getSystemLanguage()
    {
        try {
            return Capsule::table(DatabaseTable::Configuration)
                ->where('setting', 'Language')
                ->select('value')
                ->first()->value;
        } catch (\Exception $ex) {}

        return 'english';
    }
}