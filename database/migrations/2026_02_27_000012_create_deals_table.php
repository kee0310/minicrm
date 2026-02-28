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
        Schema::create('deals', function (Blueprint $table) {
            $table->id();
            // human readable code, filled later
            $table->string('deal_id')->unique()->nullable();

            $table->foreignId('client_id')->constrained('clients')->cascadeOnDelete();
            $table->string('project_name');
            $table->string('developer')->nullable();
            $table->string('unit_number')->nullable();
            $table->decimal('selling_price', 15, 2);
            $table->decimal('commission_percentage', 5, 2);
            $table->decimal('commission_amount', 15, 2)->nullable();
            $table->foreignId('salesperson_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('leader_id')->constrained('users')->restrictOnDelete();
            $table->decimal('booking_fee', 15, 2)->nullable();
            $table->date('spa_date')->nullable();
            $table->date('deal_closing_date')->nullable();
            $table->string('pipeline')->default('Lead');

            $table->timestamps();

            // indexes for large scale
            $table->index(['client_id']);
            $table->index(['salesperson_id']);
            $table->index(['leader_id']);
            $table->index(['pipeline']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('deals');
    }
};
