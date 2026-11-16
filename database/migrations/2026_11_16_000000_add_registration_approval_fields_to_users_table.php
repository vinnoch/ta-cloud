<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->enum('account_status', ['pending', 'active', 'rejected'])
                ->default('active')
                ->after('google_avatar')
                ->index();
            $table->enum('registration_method', ['google', 'admin'])
                ->default('admin')
                ->after('account_status');
            $table->foreignId('reviewed_by')
                ->nullable()
                ->after('registration_method')
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable()->after('reviewed_by');
            $table->text('rejection_reason')->nullable()->after('reviewed_at');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropForeign(['reviewed_by']);
            $table->dropIndex(['account_status']);
        });

        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn([
                'account_status',
                'registration_method',
                'reviewed_by',
                'reviewed_at',
                'rejection_reason',
            ]);
        });
    }
};
