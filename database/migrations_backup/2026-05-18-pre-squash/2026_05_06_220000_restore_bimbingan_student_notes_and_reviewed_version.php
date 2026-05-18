<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bimbingans', function (Blueprint $table) {
            if (! Schema::hasColumn('bimbingans', 'student_notes')) {
                $table->text('student_notes')->nullable()->after('meeting_date');
            }

            if (! Schema::hasColumn('bimbingans', 'reviewed_version_id')) {
                $table->foreignId('reviewed_version_id')
                    ->nullable()
                    ->after('lecturer_notes')
                    ->constrained('document_versions')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('bimbingans', function (Blueprint $table) {
            if (Schema::hasColumn('bimbingans', 'reviewed_version_id')) {
                $table->dropConstrainedForeignId('reviewed_version_id');
            }

            if (Schema::hasColumn('bimbingans', 'student_notes')) {
                $table->dropColumn('student_notes');
            }
        });
    }
};
