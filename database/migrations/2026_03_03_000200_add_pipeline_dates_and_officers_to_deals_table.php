<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected array $pipelineDateColumns = [
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
            Schema::table('deals', function (Blueprint $table) {
                if (! Schema::hasColumn('deals', 'loan_officer_id')) {
                    $table->foreignId('loan_officer_id')->nullable()->after('leader_id')->constrained('users')->nullOnDelete();
                    $table->index('loan_officer_id');
                }

                if (! Schema::hasColumn('deals', 'legal_officer_id')) {
                    $table->foreignId('legal_officer_id')->nullable()->after('loan_officer_id')->constrained('users')->nullOnDelete();
                    $table->index('legal_officer_id');
                }
            });
        }

        if (Schema::hasTable('deals')) {
            $hasLegacyPipelineDates = collect($this->pipelineDateColumns)
                ->contains(fn (string $column) => Schema::hasColumn('deals', $column));

            if ($hasLegacyPipelineDates) {
                $existingDateColumns = array_values(array_filter(
                    $this->pipelineDateColumns,
                    fn (string $column) => Schema::hasColumn('deals', $column)
                ));

                $selectColumns = array_merge(['id'], $existingDateColumns);

                DB::table('deals')
                    ->select($selectColumns)
                    ->orderBy('id')
                    ->chunkById(500, function ($rows) use ($existingDateColumns) {
                        foreach ($rows as $row) {
                            $payload = ['deal_id' => $row->id];

                            foreach ($existingDateColumns as $column) {
                                $payload[$column] = $row->{$column};
                            }

                            $payload['created_at'] = now();
                            $payload['updated_at'] = now();

                            DB::table('deal_pipelines')->upsert(
                                [$payload],
                                ['deal_id'],
                                array_merge($existingDateColumns, ['updated_at'])
                            );
                        }
                    }, 'id');
            }

            if (Schema::hasColumn('deals', 'loan_officer_id') && Schema::hasColumn('loans', 'loan_officer_id')) {
                DB::table('deals')
                    ->select(['id'])
                    ->orderBy('id')
                    ->chunkById(500, function ($rows) {
                        foreach ($rows as $row) {
                            $loanOfficerId = DB::table('loans')
                                ->where('deal_id', $row->id)
                                ->whereNotNull('loan_officer_id')
                                ->orderByDesc('updated_at')
                                ->value('loan_officer_id');

                            if (! is_null($loanOfficerId)) {
                                DB::table('deals')
                                    ->where('id', $row->id)
                                    ->update(['loan_officer_id' => $loanOfficerId]);
                            }
                        }
                    }, 'id');
            }

            if (Schema::hasColumn('deals', 'legal_officer_id') && Schema::hasColumn('legals', 'legal_officer_id')) {
                DB::table('deals')
                    ->select(['id'])
                    ->orderBy('id')
                    ->chunkById(500, function ($rows) {
                        foreach ($rows as $row) {
                            $legalOfficerId = DB::table('legals')
                                ->where('deal_id', $row->id)
                                ->whereNotNull('legal_officer_id')
                                ->value('legal_officer_id');

                            if (! is_null($legalOfficerId)) {
                                DB::table('deals')
                                    ->where('id', $row->id)
                                    ->update(['legal_officer_id' => $legalOfficerId]);
                            }
                        }
                    }, 'id');
            }
        }

        if (Schema::hasTable('loans') && Schema::hasColumn('loans', 'loan_officer_id')) {
            Schema::table('loans', function (Blueprint $table) {
                $table->dropConstrainedForeignId('loan_officer_id');
            });
        }

        if (Schema::hasTable('legals') && Schema::hasColumn('legals', 'legal_officer_id')) {
            Schema::table('legals', function (Blueprint $table) {
                $table->dropConstrainedForeignId('legal_officer_id');
            });
        }

        if (Schema::hasTable('deals') && Schema::hasColumn('deals', 'spa_date')) {
            Schema::table('deals', function (Blueprint $table) {
                $table->dropColumn('spa_date');
            });
        }

        if (Schema::hasTable('deals')) {
            $dropPipelineColumns = array_values(array_filter(
                $this->pipelineDateColumns,
                fn (string $column) => Schema::hasColumn('deals', $column)
            ));

            if (! empty($dropPipelineColumns)) {
                Schema::table('deals', function (Blueprint $table) use ($dropPipelineColumns) {
                    $table->dropColumn($dropPipelineColumns);
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('deals')) {
            Schema::table('deals', function (Blueprint $table) {
                if (! Schema::hasColumn('deals', 'spa_date')) {
                    $table->date('spa_date')->nullable()->after('booking_fee');
                }
            });

            $missingPipelineColumns = array_values(array_filter(
                $this->pipelineDateColumns,
                fn (string $column) => ! Schema::hasColumn('deals', $column)
            ));

            if (! empty($missingPipelineColumns)) {
                Schema::table('deals', function (Blueprint $table) use ($missingPipelineColumns) {
                    foreach ($missingPipelineColumns as $column) {
                        $table->dateTime($column)->nullable();
                    }
                });
            }
        }

        if (Schema::hasTable('deal_pipelines') && Schema::hasTable('deals')) {
            DB::table('deal_pipelines')
                ->select(array_merge(['deal_id'], $this->pipelineDateColumns))
                ->orderBy('deal_id')
                ->chunk(500, function ($rows) {
                    foreach ($rows as $row) {
                        $payload = [];
                        foreach ($this->pipelineDateColumns as $column) {
                            $payload[$column] = $row->{$column};
                        }

                        DB::table('deals')
                            ->where('id', $row->deal_id)
                            ->update($payload);
                    }
                });
        }

        Schema::dropIfExists('deal_pipelines');

        if (Schema::hasTable('loans') && ! Schema::hasColumn('loans', 'loan_officer_id')) {
            Schema::table('loans', function (Blueprint $table) {
                $table->foreignId('loan_officer_id')->nullable()->after('deal_id')->constrained('users')->nullOnDelete();
                $table->index('loan_officer_id');
            });
        }

        if (Schema::hasTable('legals') && ! Schema::hasColumn('legals', 'legal_officer_id')) {
            Schema::table('legals', function (Blueprint $table) {
                $table->foreignId('legal_officer_id')->nullable()->after('deal_id')->constrained('users')->nullOnDelete();
                $table->index('legal_officer_id');
            });
        }

        if (Schema::hasTable('deals') && Schema::hasColumn('deals', 'loan_officer_id') && Schema::hasTable('loans')) {
            DB::table('deals')
                ->select(['id', 'loan_officer_id'])
                ->whereNotNull('loan_officer_id')
                ->orderBy('id')
                ->chunkById(500, function ($rows) {
                    foreach ($rows as $row) {
                        DB::table('loans')
                            ->where('deal_id', $row->id)
                            ->whereNull('loan_officer_id')
                            ->update(['loan_officer_id' => $row->loan_officer_id]);
                    }
                }, 'id');
        }

        if (Schema::hasTable('deals') && Schema::hasColumn('deals', 'legal_officer_id') && Schema::hasTable('legals')) {
            DB::table('deals')
                ->select(['id', 'legal_officer_id'])
                ->whereNotNull('legal_officer_id')
                ->orderBy('id')
                ->chunkById(500, function ($rows) {
                    foreach ($rows as $row) {
                        DB::table('legals')
                            ->where('deal_id', $row->id)
                            ->whereNull('legal_officer_id')
                            ->update(['legal_officer_id' => $row->legal_officer_id]);
                    }
                }, 'id');
        }

        if (Schema::hasTable('deals') && Schema::hasColumn('deals', 'loan_officer_id')) {
            Schema::table('deals', function (Blueprint $table) {
                $table->dropConstrainedForeignId('loan_officer_id');
            });
        }

        if (Schema::hasTable('deals') && Schema::hasColumn('deals', 'legal_officer_id')) {
            Schema::table('deals', function (Blueprint $table) {
                $table->dropConstrainedForeignId('legal_officer_id');
            });
        }
    }
};
