<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('document_submissions', function (Blueprint $table) {
            $table->foreign('document_template_item_id')
                ->references('id')
                ->on('document_template_items')
                ->cascadeOnDelete();

            $table->foreign('document_version_id')
                ->references('id')
                ->on('document_versions')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('document_submissions', function (Blueprint $table) {
            $table->dropForeign(['document_template_item_id']);
            $table->dropForeign(['document_version_id']);
        });
    }
};
