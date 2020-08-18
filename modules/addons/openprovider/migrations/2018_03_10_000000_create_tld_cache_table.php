<?php
use WHMCS\Database\Capsule;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTldCacheTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if(Capsule::schema()->hasTable('OpenProviderTLDCache'))
            Capsule::schema()->drop('OpenProviderTLDCache');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
    }
}