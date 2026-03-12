<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasTable('deal_pipelines')) {
            Schema::create('deal_pipelines', function (Blueprint $table) {
                $table->id();
                $table->foreignId('deal_id')->unique()->constrained('deals')->cascadeOnDelete();
                $table->dateTime('lead_date')->nullable();
                $table->dateTime('viewing_date')->nullable();
                $table->dateTime('booking_date')->nullable();
                $table->dateTime('spa_signed_date')->nullable();
                $table->dateTime('loan_submitted_date')->nullable();
                $table->dateTime('loan_approved_date')->nullable();
                $table->dateTime('legal_processing_date')->nullable();
                $table->dateTime('completed_date')->nullable();
                $table->dateTime('commission_paid_date')->nullable();
                $table->timestamps();
            });
        }

        if (Schema::hasTable('deals')) {
            $pipelineColumns = [
                'lead_date',
                'viewing_date',
                'booking_date',
                'spa_signed_date',
                'loan_submitted_date',
                'loan_approved_date',
                'legal_processing_date',
                'completed_date',
                'commission_paid_date',
            ];

            $existingColumns = array_values(array_filter(
                $pipelineColumns,
                fn (string $column): bool => Schema::hasColumn('deals', $column)
            ));

            if (! empty($existingColumns)) {
                DB::table('deals')
                    ->select(array_merge(['id'], $existingColumns))
                    ->orderBy('id')
                    ->chunkById(500, function ($rows) use ($existingColumns) {
                        foreach ($rows as $row) {
                            $payload = [
                                'deal_id' => $row->id,
                                'created_at' => now(),
                                'updated_at' => now(),
                            ];

                            foreach ($existingColumns as $column) {
                                $payload[$column] = $row->{$column};
                            }

                            DB::table('deal_pipelines')->upsert(
                                [$payload],
                                ['deal_id'],
                                array_merge($existingColumns, ['updated_at'])
                            );
                        }
                    }, 'id');
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('deal_pipelines');
    }
};
