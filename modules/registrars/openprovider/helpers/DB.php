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

    public static function verifyContactstables()
    {
        if (!self::checkTableExist(DatabaseTable::ModContactsAdditional)) {
            //CREATE TABLES
            try {
                Capsule::schema()
                    ->create(
                        DatabaseTable::ModContactsAdditional,
                        function ($table) {
                            $table->increments('id');
                            $table->integer('contact_id');
                            $table->text('identification_type');
                            $table->text('identification_number');
                        }
                    );
            } catch (\Exception $e) {
                return [
                    // Supported values here include: success, error or info
                    'status'      => "error",
                    'description' => 'An error occured while creating tables: ' . $e->getMessage(),
                ];
            }
        }
    }

    public static function updateOrCreateContact($cord, $contactid, $id_type)
    {
        $idn = Capsule::schema()->hasTable(DatabaseTable::ModContactsAdditional) ?
        Capsule::table(DatabaseTable::ModContactsAdditional)
            ->where("contact_id", "=", $contactid)
            ->first()
            : null;

        if ($idn->contact_id) {
            try {
                $updatedUserCount = Capsule::table(DatabaseTable::ModContactsAdditional)
                    ->where('contact_id', $contactid)
                    ->update(
                        [
                            'identification_type'   => $id_type,
                            'identification_number' => $cord,
                        ]
                    );

                $msg = "Updated {$updatedUserCount} Contact";

            } catch (\Exception $e) {
                $msg = "I couldn't update Contact Company or Individual ID: . {$e->getMessage()}";
            }
        } else {
            try {
                if(!Capsule::schema()->hasTable(DatabaseTable::ModContactsAdditional)){
                    Capsule::schema()
                    ->create(
                        DatabaseTable::ModContactsAdditional,
                        function ($table) {
                            $table->increments('id');
                            $table->integer('contact_id');
                            $table->text('identification_type');
                            $table->text('identification_number');
                        }
                    );
                }
                    Capsule::table(DatabaseTable::ModContactsAdditional)->insert(
                        [
                            'contact_id'            => $contactid,
                            'identification_number' => $cord,
                            'identification_type'   => $id_type,
                        ]
                    );
    
                    $msg = "Updated {$updatedUserCount} Contact";

            } catch (\Exception $e) {
            $msg = "Uh oh! I was unable to update Contacts Company or Individual ID, but I was able to rollback. {$e->getMessage()}";
            }
        }
    }
}
