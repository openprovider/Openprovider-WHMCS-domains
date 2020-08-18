<?php
use WHMCS\Database\Capsule;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateScheduledDomainTransfersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if(!Capsule::schema()->hasTable('mod_op_scheduled_domain_transfers')) {
            Capsule::schema()->create('mod_op_scheduled_domain_transfers', function (Blueprint $table) {
                $table->increments('id');
                $table->integer('domain_id')->nullable();
                $table->string('domain', '100');
                $table->string('status', '100');
                $table->date('finished_transfer_date');
                $table->date('cron_run_date');
                $table->string('run_id', 100)->nullable();
                $table->timestamps();
            });

            Capsule::schema()->table('mod_op_scheduled_domain_transfers', function (Blueprint $table) {
                $table->foreign('domain_id')->references('id')->on('tbldomains')
                    ->onDelete('cascade');
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (Capsule::schema()->hasTable('mod_op_scheduled_domain_transfers'))
            Capsule::schema()->drop('mod_op_scheduled_domain_transfers');
    }
}