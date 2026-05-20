<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('document_template_items', function (Blueprint $table) {
            if (! Schema::hasColumn('document_template_items', 'type')) {
                $table->string('type')->default('file')->after('kode');
            }
        });
    }

    public function down(): void
    {
        Schema::table('document_template_items', function (Blueprint $table) {
            if (Schema::hasColumn('document_template_items', 'type')) {
                $table->dropColumn('type');
            }
        });
    }
};
