<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('application_settings', function (Blueprint $table): void {
            $table->unsignedSmallInteger('log_retention_days')->default(90)->after('log_retention_months');
        });

        DB::table('application_settings')->select(['id', 'log_retention_months'])->orderBy('id')->each(function (object $settings): void {
            DB::table('application_settings')->where('id', $settings->id)->update([
                'log_retention_days' => (int) $settings->log_retention_months * 30,
            ]);
        });

        Schema::table('application_settings', function (Blueprint $table): void {
            $table->dropColumn('log_retention_months');
        });
    }

    public function down(): void
    {
        Schema::table('application_settings', function (Blueprint $table): void {
            $table->unsignedTinyInteger('log_retention_months')->default(3)->after('logo_path');
        });

        DB::table('application_settings')->select(['id', 'log_retention_days'])->orderBy('id')->each(function (object $settings): void {
            DB::table('application_settings')->where('id', $settings->id)->update([
                'log_retention_months' => min(255, (int) ceil($settings->log_retention_days / 30)),
            ]);
        });

        Schema::table('application_settings', function (Blueprint $table): void {
            $table->dropColumn('log_retention_days');
        });
    }
};
