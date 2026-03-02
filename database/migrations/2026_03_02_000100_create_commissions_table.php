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
        Schema::create('commissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('deal_id')->constrained('deals')->cascadeOnDelete()->unique();
            $table->decimal('paid', 15, 2)->default(0);
            $table->string('payment_status')->default('Unpaid');
            $table->timestamps();

            $table->index('payment_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('commissions');
    }
};

