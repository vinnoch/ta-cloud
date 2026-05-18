<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('grade_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('grade_id')->constrained()->cascadeOnDelete();
            $table->foreignId('item_penilaian_id')->constrained('item_penilaians')->cascadeOnDelete();
            $table->float('score');
            $table->timestamps();

            $table->unique(['grade_id', 'item_penilaian_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('grade_items');
    }
};
