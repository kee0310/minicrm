<?php

use App\Enums\LeadStatusEnum;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('leads', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email');
            $table->string('phone')->nullable();
            $table->string('source')->nullable();
            $table->foreignId('salesperson_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('leader_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('status')->default(LeadStatusEnum::NEW->value);
            $table->unsignedTinyInteger('age')->nullable();
            $table->string('ic_passport')->nullable();
            $table->string('occupation')->nullable();
            $table->string('company')->nullable();
            $table->unsignedSmallInteger('working_years')->nullable();
            $table->decimal('monthly_income', 15, 2)->nullable();
            $table->decimal('fixed_income', 15, 2)->nullable();
            $table->timestamps();
            $table->index('email');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('leads');
    }
};
