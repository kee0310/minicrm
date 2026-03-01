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
        Schema::create('loan_pre_qualifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('deal_id')->unique()->constrained('deals')->cascadeOnDelete();
            $table->decimal('existing_loans', 15, 2)->nullable();
            $table->decimal('monthly_commitments', 15, 2)->nullable();
            $table->decimal('credit_card_limits', 15, 2)->nullable();
            $table->unsignedTinyInteger('credit_card_utilization')->nullable();
            $table->text('ccris')->nullable();
            $table->text('ctos')->nullable();
            $table->string('risk_grade', 1)->nullable();
            $table->date('pre_qualification_date')->nullable();
            $table->json('recommended_banks')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('loan_pre_qualifications');
    }
};
