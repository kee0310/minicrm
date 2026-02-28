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
        Schema::create('loan_bank_submissions', function (Blueprint $table) {
            $table->id('loan_id');
            $table->foreignId('deal_id')->constrained('deals')->cascadeOnDelete();
            $table->string('bank_name');
            $table->string('banker_contact')->nullable();
            $table->date('submission_date')->nullable();
            $table->unsignedTinyInteger('document_completeness_score')->nullable();
            $table->string('approval_status')->default('Prepared');
            $table->date('expected_approval_date')->nullable();
            $table->unsignedTinyInteger('file_completeness_percentage')->nullable();
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
        Schema::dropIfExists('loan_bank_submissions');
    }
};
