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
        Schema::create('loan_approval_analyses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('deal_id')->constrained('deals')->cascadeOnDelete();
            $table->foreignId('loan_id')->constrained('loan_bank_submissions', 'loan_id')->cascadeOnDelete();
            $table->string('approved_bank')->nullable();
            $table->decimal('applied_amount', 15, 2)->nullable();
            $table->decimal('approved_amount', 15, 2)->nullable();
            $table->decimal('interest_rate', 5, 2)->nullable();
            $table->string('lock_in_period')->nullable();
            $table->string('mrta_mlta')->nullable();
            $table->text('special_conditions')->nullable();
            $table->decimal('approval_deviation_percentage', 8, 2)->nullable();
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
        Schema::dropIfExists('loan_approval_analyses');
    }
};
