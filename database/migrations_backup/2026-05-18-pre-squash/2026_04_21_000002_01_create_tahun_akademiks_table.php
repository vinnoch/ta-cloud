<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tahun_akademiks', function (Blueprint $table) {
            $table->id();
            $table->smallInteger('tahun_awal');
            $table->smallInteger('tahun_akhir');
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tahun_awal', 'tahun_akhir']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tahun_akademiks');
    }
};
