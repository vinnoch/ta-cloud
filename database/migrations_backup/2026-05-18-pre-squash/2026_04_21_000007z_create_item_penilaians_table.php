<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('item_penilaians', function (Blueprint $table) {
            $table->id();
            $table->foreignId('format_penilaian_id')->constrained('format_penilaians')->cascadeOnDelete();
            $table->string('nama');
            $table->string('kode');
            $table->unsignedTinyInteger('bobot');
            $table->unsignedSmallInteger('sort_order')->default(1);
            $table->timestamps();

            $table->unique(['format_penilaian_id', 'kode']);
            $table->unique(['format_penilaian_id', 'sort_order']);
            $table->index(['format_penilaian_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('item_penilaians');
    }
};
