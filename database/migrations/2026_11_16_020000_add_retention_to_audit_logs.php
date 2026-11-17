<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('application_settings', function (Blueprint $table): void {
            $table->unsignedTinyInteger('log_retention_months')->default(3)->after('logo_path');
        });

        Schema::table('audit_logs', function (Blueprint $table): void {
            $table->index(['created_at', 'id'], 'audit_logs_created_at_id_index');
        });
    }

    public function down(): void
    {
        Schema::table('audit_logs', function (Blueprint $table): void {
            $table->dropIndex('audit_logs_created_at_id_index');
        });

        Schema::table('application_settings', function (Blueprint $table): void {
            $table->dropColumn('log_retention_months');
        });
    }
};
