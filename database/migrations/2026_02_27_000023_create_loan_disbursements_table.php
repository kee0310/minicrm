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
        Schema::create('loan_disbursements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('deal_id')->constrained('deals')->cascadeOnDelete();
            $table->foreignId('loan_id')->constrained('loan_bank_submissions', 'loan_id')->cascadeOnDelete();
            $table->date('first_disbursement_date')->nullable();
            $table->date('full_disbursement_date')->nullable();
            $table->date('spa_completion_date')->nullable();
            $table->date('client_notification_date')->nullable();
            $table->timestamps();

            $table->unique(['loan_id']);
            $table->index(['deal_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('loan_disbursements');
    }
};
