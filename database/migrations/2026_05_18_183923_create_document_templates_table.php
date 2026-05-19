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
        if (! Schema::hasTable('document_templates')) {
            Schema::create('document_templates', function (Blueprint $table) {
                $table->id();
                $table->foreignId('study_program_id')->nullable()->constrained('study_programs')->nullOnDelete();
                $table->string('nama');
                $table->boolean('is_published')->default(false);
                $table->boolean('is_locked')->default(false);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('document_template_periode')) {
            Schema::create('document_template_periode', function (Blueprint $table) {
                $table->id();
                $table->foreignId('document_template_id')->constrained('document_templates')->cascadeOnDelete();
                $table->foreignId('periode_id')->constrained('periodes')->cascadeOnDelete();
                $table->timestamps();

                $table->unique(['document_template_id', 'periode_id']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('document_template_periode');
        Schema::dropIfExists('document_templates');
    }
};
