<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('clients', function (Blueprint $table) {
            $table->id();
            // human readable code, filled later
            $table->string('client_id')->nullable()->unique();

            $table->string('name');
            $table->string('email')->unique();
            $table->string('phone')->nullable();
            $table->foreignId('salesperson_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('leader_id')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedTinyInteger('age')->nullable();
            $table->string('ic_passport')->nullable();
            $table->string('occupation')->nullable();
            $table->string('company')->nullable();
            $table->decimal('monthly_income', 15, 2)->nullable();
            $table->string('status')->default('New');
            $table->unsignedTinyInteger('completeness_rate')->default(0);

            $table->timestamps();

            // indexes for large scale
            $table->index(['salesperson_id']);
            $table->index(['leader_id']);
            $table->index(['status']);
        });

        // bootstrap clients from existing leads that are already in Deal status
        $now = now();
        $rows = DB::table('leads')
            ->where('status', \App\Enums\LeadStatusEnum::DEAL->value)
            ->select('name', 'email', 'phone', 'salesperson_id', 'leader_id')
            ->get()
            ->map(function ($lead) use ($now) {
                return [
                    'name' => $lead->name,
                    'email' => $lead->email,
                    'phone' => $lead->phone,
                    'salesperson_id' => $lead->salesperson_id,
                    'leader_id' => $lead->leader_id,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            })
            ->all();

        if (!empty($rows)) {
            DB::table('clients')->insert($rows);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('clients');
    }
};
