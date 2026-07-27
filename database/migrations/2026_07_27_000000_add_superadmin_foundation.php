<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('users_level')->updateOrInsert(
            ['users_level' => 'superadmin'],
            ['created_at' => now(), 'updated_at' => now()],
        );

        Schema::create('audit_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('actor_email')->nullable();
            $table->string('action');
            $table->string('target_type')->nullable();
            $table->unsignedBigInteger('target_id')->nullable();
            $table->json('before')->nullable();
            $table->json('after')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();
            $table->index(['target_type', 'target_id']);
        });

        Schema::create('application_settings', function (Blueprint $table): void {
            $table->id();
            $table->string('application_name')->default('TA Cloud UKWK');
            $table->string('logo_path')->nullable();
            $table->timestamps();
        });

        DB::table('application_settings')->insert([
            'application_name' => 'TA Cloud UKWK',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('application_settings');
        Schema::dropIfExists('audit_logs');
        DB::table('users_level')->where('users_level', 'superadmin')->delete();
    }
};
