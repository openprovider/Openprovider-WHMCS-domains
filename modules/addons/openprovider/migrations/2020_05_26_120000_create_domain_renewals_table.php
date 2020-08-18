<?php
use WHMCS\Database\Capsule;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateDomainRenewalsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if(!Capsule::schema()->hasTable('mod_op_scheduled_domain_renewals')) {
            Capsule::schema()->create('mod_op_scheduled_domain_renewals', function (Blueprint $table) {
                $table->increments('id');
                $table->integer('domain_id');
                $table->text('domain');
                $table->date('original_expiry_date');
                $table->date('new_expiry_date');
                $table->timestamps();
            });

            Capsule::schema()->table('mod_op_scheduled_domain_renewals', function (Blueprint $table) {
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
        if(Capsule::schema()->hasTable('mod_op_scheduled_domain_renewals'))
            Capsule::schema()->drop('mod_op_scheduled_domain_renewals');
    }
}