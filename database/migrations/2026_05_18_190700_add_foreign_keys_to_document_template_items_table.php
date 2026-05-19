<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('document_template_items')) {
            Schema::table('document_template_items', function (Blueprint $table) {
                $table->foreign('document_template_id')
                    ->references('id')
                    ->on('document_templates')
                    ->cascadeOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('document_template_items')) {
            Schema::table('document_template_items', function (Blueprint $table) {
                $table->dropForeign(['document_template_id']);
            });
        }
    }
};
