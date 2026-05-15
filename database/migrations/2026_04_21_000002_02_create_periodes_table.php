<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('periodes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tahun_akademik_id')->constrained('tahun_akademiks')->cascadeOnDelete();
            $table->string('kode_periode');
            $table->tinyInteger('semester');
            $table->string('sk_nomor')->nullable();
            $table->string('sk_dokumen_url')->nullable();
            $table->date('tgl_mulai');
            $table->date('tgl_selesai');
            $table->boolean('is_aktif')->default(false)->index();
            $table->string('status')->default('draft')->index();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tahun_akademik_id', 'kode_periode']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('periodes');
    }
};
