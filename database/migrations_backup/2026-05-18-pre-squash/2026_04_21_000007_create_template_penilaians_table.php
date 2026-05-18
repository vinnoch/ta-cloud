<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('format_penilaians', function (Blueprint $table) {
            $table->id();
            $table->foreignId('study_program_id')->nullable()->constrained('study_programs')->cascadeOnDelete();
            $table->string('template_type')->default('sidang_skripsi')->index();
            $table->string('nama');
            $table->boolean('is_published')->default(false)->index();
            $table->boolean('is_locked')->default(false)->index();
            $table->boolean('is_default')->default(false)->index();
            $table->timestamps();

            $table->index(['study_program_id', 'is_published']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('format_penilaians');
    }
};
