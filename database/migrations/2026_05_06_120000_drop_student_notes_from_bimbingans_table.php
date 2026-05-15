<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        // Keep student_notes. Mahasiswa edit flow depends on this column.
    }

    public function down(): void
    {
        // No-op.
    }
};
