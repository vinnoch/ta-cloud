<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('skripsis', function (Blueprint $table) {
            if (! Schema::hasColumn('skripsis', 'sidang_proposal_datetime')) {
                $table->dateTime('sidang_proposal_datetime')->nullable()->after('journal_article_url');
            }

            if (! Schema::hasColumn('skripsis', 'sidang_proposal_grade_notified_at')) {
                $table->dateTime('sidang_proposal_grade_notified_at')->nullable()->after('sidang_proposal_datetime');
            }
        });
    }

    public function down(): void
    {
        Schema::table('skripsis', function (Blueprint $table) {
            $columns = [];

            if (Schema::hasColumn('skripsis', 'sidang_proposal_datetime')) {
                $columns[] = 'sidang_proposal_datetime';
            }

            if (Schema::hasColumn('skripsis', 'sidang_proposal_grade_notified_at')) {
                $columns[] = 'sidang_proposal_grade_notified_at';
            }

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }
};
