<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('final_document_approvals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('skripsi_id')->constrained('skripsis')->cascadeOnDelete();
            $table->foreignId('document_version_id')->constrained('document_versions')->cascadeOnDelete();
            $table->foreignId('reviewer_id')->constrained('users')->cascadeOnDelete();
            $table->string('role_type');
            $table->string('status')->default('pending');
            $table->text('note')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();

            $table->unique(['document_version_id', 'reviewer_id']);
            $table->index(['skripsi_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('final_document_approvals');
    }
};
