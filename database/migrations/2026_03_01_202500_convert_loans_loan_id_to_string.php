<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('loans')) {
            return;
        }

        $driver = DB::getDriverName();
        if ($driver !== 'mysql') {
            return;
        }

        $column = DB::selectOne("SHOW COLUMNS FROM loans LIKE 'loan_id'");
        if (!$column) {
            return;
        }

        $type = strtolower((string) ($column->Type ?? ''));
        if (str_contains($type, 'varchar')) {
            return;
        }

        DB::statement('ALTER TABLE loans DROP PRIMARY KEY');
        DB::statement('ALTER TABLE loans MODIFY loan_id VARCHAR(20) NOT NULL');
        DB::statement("UPDATE loans SET loan_id = CONCAT('LO-', LPAD(CAST(loan_id AS UNSIGNED), 6, '0'))");
        DB::statement('ALTER TABLE loans ADD PRIMARY KEY (loan_id)');
    }

    public function down(): void
    {
        if (!Schema::hasTable('loans')) {
            return;
        }

        $driver = DB::getDriverName();
        if ($driver !== 'mysql') {
            return;
        }

        $column = DB::selectOne("SHOW COLUMNS FROM loans LIKE 'loan_id'");
        if (!$column) {
            return;
        }

        $type = strtolower((string) ($column->Type ?? ''));
        if (!str_contains($type, 'varchar')) {
            return;
        }

        DB::statement('ALTER TABLE loans DROP PRIMARY KEY');
        DB::statement("UPDATE loans SET loan_id = CAST(SUBSTRING(loan_id, 4) AS UNSIGNED) WHERE loan_id LIKE 'LO-%'");
        DB::statement('ALTER TABLE loans MODIFY loan_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT');
        DB::statement('ALTER TABLE loans ADD PRIMARY KEY (loan_id)');
    }
};
