<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('grades', function (Blueprint $table) {
            $table->id();
            $table->foreignId('skripsi_id')->constrained()->cascadeOnDelete();
            $table->foreignId('format_penilaian_id')->constrained('format_penilaians')->cascadeOnDelete();
            $table->foreignId('reviewer_id')->constrained('users')->cascadeOnDelete();
            $table->string('role_type');
            $table->string('grade_event');
            $table->string('status')->default('draft');
            $table->float('score')->nullable();
            $table->timestamps();

            $table->unique(['skripsi_id', 'format_penilaian_id', 'reviewer_id', 'grade_event'], 'grades_unique_event');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('grades');
    }
};
