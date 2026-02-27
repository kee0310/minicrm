<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement('ALTER TABLE clients DROP FOREIGN KEY clients_lead_id_foreign');
        DB::statement('ALTER TABLE clients MODIFY lead_id BIGINT UNSIGNED NULL');
        DB::statement('ALTER TABLE clients ADD CONSTRAINT clients_lead_id_foreign FOREIGN KEY (lead_id) REFERENCES leads(id) ON DELETE SET NULL');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('ALTER TABLE clients DROP FOREIGN KEY clients_lead_id_foreign');
        DB::statement('ALTER TABLE clients MODIFY lead_id BIGINT UNSIGNED NOT NULL');
        DB::statement('ALTER TABLE clients ADD CONSTRAINT clients_lead_id_foreign FOREIGN KEY (lead_id) REFERENCES leads(id) ON DELETE CASCADE');
    }
};
