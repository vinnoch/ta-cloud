<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('format_periode', function (Blueprint $table) {
            $table->id();
            $table->foreignId('format_penilaian_id')->constrained('format_penilaians')->cascadeOnDelete();
            $table->foreignId('periode_id')->constrained('periodes')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['format_penilaian_id', 'periode_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('format_periode');
    }
};
