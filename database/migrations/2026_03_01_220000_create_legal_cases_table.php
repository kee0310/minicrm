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
        Schema::create('legal_cases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('deal_id')->constrained('deals')->cascadeOnDelete()->unique();
            $table->string('status')->default('Drafting');
            $table->string('lawyer_firm')->nullable();
            $table->date('spa_date')->nullable();
            $table->date('loan_agreement_date')->nullable();
            $table->date('completion_date')->nullable();
            $table->boolean('stamp_duty')->default(false);
            $table->timestamps();

            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('legal_cases');
    }
};
