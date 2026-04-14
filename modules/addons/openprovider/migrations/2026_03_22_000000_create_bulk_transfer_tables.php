<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use WHMCS\Database\Capsule;

class CreateBulkTransferTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Capsule::schema()->hasTable('mod_op_bulk_transfer_batches')) {
            Capsule::schema()->create('mod_op_bulk_transfer_batches', function (Blueprint $table) {
                $table->increments('id');
                $table->string('bulk_reference', 50)->unique('uniq_bulk_reference');
                $table->integer('reseller_id')->unsigned()->nullable()->default(null);
                $table->integer('initiated_by_admin_id')->unsigned()->nullable()->default(null);
                $table->string('description', 255)->nullable()->default(null);
                $table->integer('total_domains')->unsigned()->default(0);
                $table->integer('processed_domains')->unsigned()->default(0);
                $table->integer('success_domains')->unsigned()->default(0);
                $table->integer('failed_domains')->unsigned()->default(0);
                $table->enum('status', [
                    'queued',
                    'processing',
                    'completed',
                    'completed_with_errors',
                    'failed',
                ])->default('queued')->index('idx_status');
                $table->text('notes')->nullable();
                $table->dateTime('created_at')->useCurrent();
                $table->dateTime('updated_at')->useCurrent()->useCurrentOnUpdate();

                $table->index('created_at', 'idx_created_at');
            });

        }

        if (!Capsule::schema()->hasTable('mod_op_bulk_transfer_items')) {
            Capsule::schema()->create('mod_op_bulk_transfer_items', function (Blueprint $table) {
                $table->increments('id');
                $table->integer('batch_id')->unsigned();
                $table->integer('client_id')->unsigned()->nullable()->default(null);
                $table->integer('domain_id')->unsigned()->nullable()->default(null);
                $table->string('domain', 255);
                $table->string('op_owner_handle', 100)->nullable()->default(null);
                $table->string('op_admin_handle', 100)->nullable()->default(null);
                $table->string('op_tech_handle', 100)->nullable()->default(null);
                $table->string('op_billing_handle', 100)->nullable()->default(null);
                $table->string('op_transfer_status', 20)->nullable()->default(null);
                $table->integer('op_domain_id')->unsigned()->nullable()->default(null);
                $table->enum('transfer_status', [
                    'queued',
                    'validating',
                    'validation_failed',
                    'ready_for_transfer',
                    'unlocking',
                    'getting_epp',
                    'creating_handle',
                    'transferring',
                    'transfer_requested',
                    'checking_transfer_status',
                    'success',
                    'failed',
                ])->default('queued')->index('idx_transfer_status');
                $table->text('failure_reason')->nullable();
                $table->dateTime('transfer_requested_at')->nullable()->default(null);
                $table->dateTime('last_status_check_at')->nullable()->default(null);
                $table->text('last_status_message')->nullable();
                $table->tinyInteger('attempt_count')->unsigned()->default(0);
                $table->dateTime('started_at')->nullable()->default(null);
                $table->dateTime('finished_at')->nullable()->default(null);
                $table->dateTime('created_at')->useCurrent();
                $table->dateTime('updated_at')->useCurrent()->useCurrentOnUpdate();

                $table->unique(['batch_id', 'domain'], 'uniq_batch_domain');
                $table->index('batch_id', 'idx_batch_id');
                $table->index('client_id', 'idx_client_id');
                $table->index('domain_id', 'idx_domain_id');
                $table->index('op_transfer_status', 'idx_op_transfer_status');
                $table->index('last_status_check_at', 'idx_last_status_check_at');
                $table->index('domain', 'idx_domain');
            });

            Capsule::schema()->table('mod_op_bulk_transfer_items', function (Blueprint $table) {
                $table->foreign('batch_id', 'fk_bulk_transfer_batch')
                    ->references('id')
                    ->on('mod_op_bulk_transfer_batches')
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
        if (Capsule::schema()->hasTable('mod_op_bulk_transfer_items')) {
            Capsule::schema()->table('mod_op_bulk_transfer_items', function (Blueprint $table) {
                $table->dropForeign('fk_bulk_transfer_batch');
            });
            Capsule::schema()->drop('mod_op_bulk_transfer_items');
        }

        if (Capsule::schema()->hasTable('mod_op_bulk_transfer_batches')) {
            Capsule::schema()->drop('mod_op_bulk_transfer_batches');
        }
    }
}
