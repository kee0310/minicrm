<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('deals', function (Blueprint $table) {
            $table->index('created_at', 'deals_created_at_idx');
            $table->index(['pipeline', 'loan_officer_id'], 'deals_pipeline_loan_officer_idx');
            $table->index(['pipeline', 'salesperson_id'], 'deals_pipeline_salesperson_idx');
            $table->index(['salesperson_id', 'created_at'], 'deals_salesperson_created_at_idx');
            $table->index(['leader_id', 'created_at'], 'deals_leader_created_at_idx');
        });

        if (Schema::getConnection()->getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE leads ADD INDEX leads_status_idx (status(50))');
            DB::statement('ALTER TABLE leads ADD INDEX leads_created_at_idx (created_at)');
            DB::statement('ALTER TABLE leads ADD INDEX leads_status_created_at_idx (status(50), created_at)');
            DB::statement('ALTER TABLE leads ADD INDEX leads_salesperson_status_idx (salesperson_id, status(50))');
            DB::statement('ALTER TABLE leads ADD INDEX leads_leader_status_idx (leader_id, status(50))');
        } else {
            Schema::table('leads', function (Blueprint $table) {
                $table->index('status', 'leads_status_idx');
                $table->index('created_at', 'leads_created_at_idx');
                $table->index(['status', 'created_at'], 'leads_status_created_at_idx');
                $table->index(['salesperson_id', 'status'], 'leads_salesperson_status_idx');
                $table->index(['leader_id', 'status'], 'leads_leader_status_idx');
            });
        }

        Schema::table('loans', function (Blueprint $table) {
            $table->index('bank_name', 'loans_bank_name_idx');
            $table->index('created_at', 'loans_created_at_idx');
            $table->index('updated_at', 'loans_updated_at_idx');
            $table->index(['deal_id', 'updated_at'], 'loans_deal_updated_at_idx');
            $table->index(['approval_status', 'created_at'], 'loans_status_created_at_idx');
            $table->index(['deal_id', 'approval_status'], 'loans_deal_status_idx');
        });

        if (Schema::hasTable('deal_pipelines')) {
            Schema::table('deal_pipelines', function (Blueprint $table) {
                $table->index('loan_approved_date', 'deal_pipelines_loan_approved_date_idx');
                $table->index('completed_date', 'deal_pipelines_completed_date_idx');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('deal_pipelines')) {
            Schema::table('deal_pipelines', function (Blueprint $table) {
                $table->dropIndex('deal_pipelines_loan_approved_date_idx');
                $table->dropIndex('deal_pipelines_completed_date_idx');
            });
        }

        Schema::table('loans', function (Blueprint $table) {
            $table->dropIndex('loans_bank_name_idx');
            $table->dropIndex('loans_created_at_idx');
            $table->dropIndex('loans_updated_at_idx');
            $table->dropIndex('loans_deal_updated_at_idx');
            $table->dropIndex('loans_status_created_at_idx');
            $table->dropIndex('loans_deal_status_idx');
        });

        Schema::table('leads', function (Blueprint $table) {
            $table->dropIndex('leads_status_idx');
            $table->dropIndex('leads_created_at_idx');
            $table->dropIndex('leads_status_created_at_idx');
            $table->dropIndex('leads_salesperson_status_idx');
            $table->dropIndex('leads_leader_status_idx');
        });

        Schema::table('deals', function (Blueprint $table) {
            $table->dropIndex('deals_created_at_idx');
            $table->dropIndex('deals_pipeline_loan_officer_idx');
            $table->dropIndex('deals_pipeline_salesperson_idx');
            $table->dropIndex('deals_salesperson_created_at_idx');
            $table->dropIndex('deals_leader_created_at_idx');
        });
    }
};
