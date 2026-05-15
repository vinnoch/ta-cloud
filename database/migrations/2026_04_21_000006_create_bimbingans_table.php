<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bimbingans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('skripsi_id')->constrained()->cascadeOnDelete();
            $table->foreignId('reviewer_id')->constrained('users')->cascadeOnDelete();
            $table->string('phase');
            $table->date('meeting_date');
            $table->text('student_notes')->nullable();
            $table->text('lecturer_notes')->nullable();
            $table->string('revision_file_url')->nullable();
            $table->foreignId('reviewed_version_id')->nullable()->constrained('document_versions')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bimbingans');
    }
};
