<?php
use WHMCS\Database\Capsule;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

/**
 * Class DropOpLegacyTables
 * OpenProvider Registrar module
 *
 * @copyright Copyright (c) Openprovider 2018
 */

class DropOpLegacyTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if(Capsule::schema()->hasTable('OpenProviderHandles'))
            Capsule::schema()->drop('OpenProviderHandles');
        
        if(Capsule::schema()->hasTable('OpenproviderCache'))
            Capsule::schema()->drop('OpenproviderCache');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // There is no way back.
    }
}