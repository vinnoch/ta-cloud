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
        if (! Schema::hasTable('document_submissions')) {
            Schema::create('document_submissions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('skripsi_id')->constrained('skripsis')->cascadeOnDelete();
                $table->unsignedBigInteger('document_template_item_id');
                $table->unsignedBigInteger('document_version_id')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->unique(['skripsi_id', 'document_template_item_id']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('document_submissions');
    }
};
