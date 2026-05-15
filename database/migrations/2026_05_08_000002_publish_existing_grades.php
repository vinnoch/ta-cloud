<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('grades') || ! Schema::hasColumn('grades', 'status')) {
            return;
        }

        DB::table('grades')
            ->whereIn('status', ['draft', 'final'])
            ->update(['status' => 'published']);

        $driver = DB::getDriverName();
        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE grades MODIFY status VARCHAR(255) NOT NULL DEFAULT 'published'");
        } elseif ($driver === 'sqlite') {
            // SQLite keeps old default; app writes `published` explicitly.
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('grades') || ! Schema::hasColumn('grades', 'status')) {
            return;
        }

        $driver = DB::getDriverName();
        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE grades MODIFY status VARCHAR(255) NOT NULL DEFAULT 'draft'");
        }
    }
};
