<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_roles', function (Blueprint $table): void {
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('users_id');
            $table->foreign('users_id')->references('users_id')->on('users_level')->cascadeOnDelete();
            $table->timestamps();
            $table->primary(['user_id', 'users_id']);
        });

        DB::table('user_roles')->insertOrIgnoreUsing(
            ['user_id', 'users_id', 'created_at', 'updated_at'],
            DB::table('users')
                ->join('users_level', 'users_level.users_level', '=', 'users.role')
                ->select('users.id', 'users_level.users_id', 'users.created_at', 'users.updated_at')
        );

        DB::table('user_roles')->insertOrIgnoreUsing(
            ['user_id', 'users_id', 'created_at', 'updated_at'],
            DB::table('users')
                ->crossJoin('users_level')
                ->where('users.email', 'vinno@widyakarya.ac.id')
                ->where('users_level.users_level', 'dosen')
                ->select('users.id', 'users_level.users_id', 'users.created_at', 'users.updated_at')
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('user_roles');
    }
};
