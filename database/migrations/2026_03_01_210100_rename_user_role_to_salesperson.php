<?php

use App\Enums\RoleEnum;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('roles')) {
            return;
        }

        $hasSalesperson = DB::table('roles')
            ->where('name', RoleEnum::SALESPERSON->value)
            ->exists();

        if ($hasSalesperson) {
            return;
        }

        DB::table('roles')
            ->where('name', 'User')
            ->update(['name' => RoleEnum::SALESPERSON->value]);
    }

    public function down(): void
    {
        if (!Schema::hasTable('roles')) {
            return;
        }

        $hasUser = DB::table('roles')
            ->where('name', 'User')
            ->exists();

        if ($hasUser) {
            return;
        }

        DB::table('roles')
            ->where('name', RoleEnum::SALESPERSON->value)
            ->update(['name' => 'User']);
    }
};
