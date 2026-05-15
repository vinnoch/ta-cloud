<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('skripsis', function (Blueprint $table) {
            $table->string('proposal_review_status')->default('pending')->after('current_phase')->index();
            $table->timestamp('proposal_reviewed_at')->nullable()->after('proposal_review_status');
            $table->text('proposal_review_note')->nullable()->after('proposal_reviewed_at');
        });
    }

    public function down(): void
    {
        Schema::table('skripsis', function (Blueprint $table) {
            $table->dropColumn(['proposal_review_status', 'proposal_reviewed_at', 'proposal_review_note']);
        });
    }
};
