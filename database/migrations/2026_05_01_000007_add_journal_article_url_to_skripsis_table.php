<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('skripsis', function (Blueprint $table) {
            $table->string('journal_article_url')->nullable()->after('current_phase');
        });
    }

    public function down(): void
    {
        Schema::table('skripsis', function (Blueprint $table) {
            $table->dropColumn('journal_article_url');
        });
    }
};
