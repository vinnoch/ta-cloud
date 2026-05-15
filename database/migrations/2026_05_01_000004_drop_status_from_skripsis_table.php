<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('skripsis', 'status')) {
            return;
        }

        Schema::table('skripsis', function (Blueprint $table) {
            try {
                $table->dropIndex('skripsis_status_index');
            } catch (Throwable $e) {
            }
        });

        Schema::table('skripsis', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn('skripsis', 'status')) {
            return;
        }

        Schema::table('skripsis', function (Blueprint $table) {
            $table->string('status')->nullable()->after('type');
            $table->index('status');
        });
    }
};
