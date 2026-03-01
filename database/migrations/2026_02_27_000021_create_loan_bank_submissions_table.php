<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('loans', function (Blueprint $table) {
            $table->id('loan_id');
            $table->foreignId('deal_id')->constrained('deals')->cascadeOnDelete();

            // Bank Submission Tracking fields
            $table->string('bank_name');
            $table->string('banker_contact')->nullable();
            $table->date('submission_date')->nullable();
            $table->unsignedTinyInteger('document_completeness_score')->nullable();
            $table->string('approval_status')->default('Prepared');
            $table->date('expected_approval_date')->nullable();
            $table->unsignedTinyInteger('file_completeness_percentage')->nullable();

            // Approval Analysis fields
            $table->string('approved_bank')->nullable();
            $table->decimal('applied_amount', 15, 2)->nullable();
            $table->decimal('approved_amount', 15, 2)->nullable();
            $table->decimal('interest_rate', 5, 2)->nullable();
            $table->string('lock_in_period')->nullable();
            $table->string('mrta_mlta')->nullable();
            $table->text('special_conditions')->nullable();
            $table->decimal('approval_deviation_percentage', 8, 2)->nullable();

            // Disbursement fields
            $table->date('first_disbursement_date')->nullable();
            $table->date('full_disbursement_date')->nullable();
            $table->date('spa_completion_date')->nullable();
            $table->date('client_notification_date')->nullable();

            $table->timestamps();

            $table->index(['deal_id']);
            $table->index(['loan_id']);
            $table->index(['approval_status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('loans');
    }
};
