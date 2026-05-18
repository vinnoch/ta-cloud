<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('non_skripsi_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('skripsi_id')->constrained()->cascadeOnDelete();
            $table->text('summary');
            $table->text('abstract');
            $table->string('report_path')->nullable();
            $table->string('publication_url')->nullable();
            $table->float('final_score')->nullable();
            $table->timestamps();

            $table->unique('skripsi_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('non_skripsi_records');
    }
};
