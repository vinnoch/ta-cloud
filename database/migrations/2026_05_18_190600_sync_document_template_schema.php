<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('document_templates')) {
            Schema::table('document_templates', function (Blueprint $table) {
                if (! Schema::hasColumn('document_templates', 'study_program_id')) {
                    $table->foreignId('study_program_id')->nullable()->after('id')->constrained('study_programs')->nullOnDelete();
                }
                if (! Schema::hasColumn('document_templates', 'nama')) {
                    $table->string('nama')->after('study_program_id');
                }
                if (! Schema::hasColumn('document_templates', 'is_published')) {
                    $table->boolean('is_published')->default(false)->after('nama');
                }
                if (! Schema::hasColumn('document_templates', 'is_locked')) {
                    $table->boolean('is_locked')->default(false)->after('is_published');
                }
            });
        }

        if (! Schema::hasTable('document_template_periode')) {
            Schema::create('document_template_periode', function (Blueprint $table) {
                $table->id();
                $table->foreignId('document_template_id')->constrained('document_templates')->cascadeOnDelete();
                $table->foreignId('periode_id')->constrained('periodes')->cascadeOnDelete();
                $table->timestamps();
                $table->unique(['document_template_id', 'periode_id']);
            });
        }

        if (Schema::hasTable('document_template_items')) {
            Schema::table('document_template_items', function (Blueprint $table) {
                if (! Schema::hasColumn('document_template_items', 'document_template_id')) {
                    $table->unsignedBigInteger('document_template_id')->nullable()->after('id');
                }
                if (! Schema::hasColumn('document_template_items', 'nama')) {
                    $table->string('nama')->nullable()->after('document_template_id');
                }
                if (! Schema::hasColumn('document_template_items', 'kode')) {
                    $table->string('kode')->nullable()->after('nama');
                }
                if (! Schema::hasColumn('document_template_items', 'is_required')) {
                    $table->boolean('is_required')->default(true)->after('kode');
                }
                if (! Schema::hasColumn('document_template_items', 'sort_order')) {
                    $table->unsignedInteger('sort_order')->default(1)->after('is_required');
                }
            });
        }
    }

    public function down(): void
    {
    }
};
