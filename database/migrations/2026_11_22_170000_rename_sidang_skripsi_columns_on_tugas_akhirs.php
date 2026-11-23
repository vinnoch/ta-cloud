<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('tugas_akhirs')) {
            return;
        }

        if (Schema::hasColumn('tugas_akhirs', 'sidang_skripsi_datetime')
            && ! Schema::hasColumn('tugas_akhirs', 'sidang_tugas_akhir_datetime')) {
            Schema::table('tugas_akhirs', function (Blueprint $table): void {
                $table->renameColumn('sidang_skripsi_datetime', 'sidang_tugas_akhir_datetime');
            });
        }

        if (Schema::hasColumn('tugas_akhirs', 'sidang_skripsi_grade_notified_at')
            && ! Schema::hasColumn('tugas_akhirs', 'sidang_tugas_akhir_grade_notified_at')) {
            Schema::table('tugas_akhirs', function (Blueprint $table): void {
                $table->renameColumn('sidang_skripsi_grade_notified_at', 'sidang_tugas_akhir_grade_notified_at');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('tugas_akhirs')) {
            return;
        }

        if (Schema::hasColumn('tugas_akhirs', 'sidang_tugas_akhir_datetime')
            && ! Schema::hasColumn('tugas_akhirs', 'sidang_skripsi_datetime')) {
            Schema::table('tugas_akhirs', function (Blueprint $table): void {
                $table->renameColumn('sidang_tugas_akhir_datetime', 'sidang_skripsi_datetime');
            });
        }

        if (Schema::hasColumn('tugas_akhirs', 'sidang_tugas_akhir_grade_notified_at')
            && ! Schema::hasColumn('tugas_akhirs', 'sidang_skripsi_grade_notified_at')) {
            Schema::table('tugas_akhirs', function (Blueprint $table): void {
                $table->renameColumn('sidang_tugas_akhir_grade_notified_at', 'sidang_skripsi_grade_notified_at');
            });
        }
    }
};
