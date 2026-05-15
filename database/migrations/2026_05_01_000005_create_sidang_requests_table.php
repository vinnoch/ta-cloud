<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sidang_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('skripsi_id')->constrained('skripsis')->cascadeOnDelete();
            $table->foreignId('lecturer_id')->constrained('users')->cascadeOnDelete();
            $table->string('role_type');
            $table->string('status')->default('submitted')->index();
            $table->text('note')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['skripsi_id', 'lecturer_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sidang_requests');
    }
};
