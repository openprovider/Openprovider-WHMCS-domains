<?php
use WHMCS\Database\Capsule;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class WpowerCreateHandlesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Capsule::schema()->create('wHandles', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('user_id')->unsigned();
            $table->string('registrar');
            $table->string('type');
            $table->string('handle');
            $table->text('data');
            $table->timestamps();
        });
        // For creating
        Capsule::schema()->create('wDomain_handle', function (Blueprint $table) {
            $table->increments('id');
            $table->string('type');
            $table->integer('domain_id')->unsigned();
            $table->integer('handle_id')->unsigned();
            $table->timestamps();
            $table->foreign('handle_id')->references('id')->on('wHandles');
        });
    }
    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Capsule::schema()->drop('wHandles');
        Capsule::schema()->drop('wDomain_handle');
    }
}