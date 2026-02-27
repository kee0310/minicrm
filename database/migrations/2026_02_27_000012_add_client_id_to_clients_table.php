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
        Schema::table('clients', function (Blueprint $table) {
            $table->string('client_id')->nullable()->unique()->after('id');
        });

        $clients = DB::table('clients')->select('id')->orderBy('id')->get();
        foreach ($clients as $client) {
            DB::table('clients')
                ->where('id', $client->id)
                ->update(['client_id' => sprintf('CL-%06d', $client->id)]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropUnique(['client_id']);
            $table->dropColumn('client_id');
        });
    }
};
