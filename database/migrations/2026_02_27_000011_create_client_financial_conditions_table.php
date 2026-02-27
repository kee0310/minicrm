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
        Schema::create('client_financial', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->unique()->constrained('clients')->cascadeOnDelete();
            $table->decimal('existing_loans', 15, 2)->nullable();
            $table->decimal('monthly_commitments', 15, 2)->nullable();
            $table->decimal('credit_card_limits', 15, 2)->nullable();
            $table->decimal('credit_card_utilization', 5, 2)->nullable();
            $table->text('ccris')->nullable();
            $table->text('ctos')->nullable();
            $table->string('risk_grade', 1)->nullable();
            $table->timestamps();
        });

        $now = now();
        $clientIds = DB::table('clients')
            ->whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('client_financial')
                    ->whereColumn('client_financial.client_id', 'clients.id');
            })
            ->pluck('id');

        if ($clientIds->isNotEmpty()) {
            $rows = $clientIds->map(function ($clientId) use ($now) {
                return [
                    'client_id' => $clientId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            })->all();

            DB::table('client_financial')->insert($rows);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('client_financial');
    }
};
