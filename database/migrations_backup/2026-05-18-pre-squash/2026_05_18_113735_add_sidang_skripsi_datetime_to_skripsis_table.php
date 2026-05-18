<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('skripsis', function (Blueprint $table) {
            $table->dateTime('sidang_skripsi_datetime')->nullable()->after('journal_article_url');
            $table->dateTime('sidang_skripsi_grade_notified_at')->nullable()->after('sidang_skripsi_datetime');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('skripsis', function (Blueprint $table) {
            $table->dropColumn([
                'sidang_skripsi_datetime',
                'sidang_skripsi_grade_notified_at',
            ]);
        });
    }
};
