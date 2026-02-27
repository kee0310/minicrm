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
            $table->foreignId('lead_id')->unique()->constrained('leads')->cascadeOnDelete();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('phone')->nullable();
            $table->unsignedTinyInteger('age')->nullable();
            $table->string('ic_passport')->nullable();
            $table->string('occupation')->nullable();
            $table->string('company')->nullable();
            $table->decimal('monthly_income', 15, 2)->nullable();
            $table->string('status')->default('New');
            $table->unsignedTinyInteger('completeness_rate')->default(0);
            $table->timestamps();
        });

        $now = now();
        $dealLeads = DB::table('leads')
            ->where('status', \App\Enums\LeadStatusEnum::DEAL->value)
            ->whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('clients')
                    ->whereColumn('clients.lead_id', 'leads.id');
            })
            ->select('id as lead_id', 'name', 'email', 'phone')
            ->get();

        if ($dealLeads->isNotEmpty()) {
            $rows = $dealLeads->map(function ($lead) use ($now) {
                return [
                    'lead_id' => $lead->lead_id,
                    'name' => $lead->name,
                    'email' => $lead->email,
                    'phone' => $lead->phone,
                    'status' => 'New',
                    'completeness_rate' => 0,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            })->all();

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
