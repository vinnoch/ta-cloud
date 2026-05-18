<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('skripsis', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->foreignId('periode_id')->constrained('periodes')->cascadeOnDelete();
            $table->string('title');
            $table->enum('type', ['skripsi', 'non_skripsi'])->default('skripsi');
            $table->string('status')->default('pending')->index();
            $table->string('current_phase')->default('proposal')->index();
            $table->timestamps();

            $table->index(['periode_id', 'current_phase']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('skripsis');
    }
};
