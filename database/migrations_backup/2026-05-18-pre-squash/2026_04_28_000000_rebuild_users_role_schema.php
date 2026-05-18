<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users_level', function (Blueprint $table) {
            $table->id('users_id');
            $table->string('users_level', 50)->unique();
            $table->timestamps();
        });

        DB::table('users_level')->insert([
            ['users_level' => 'admin', 'created_at' => now(), 'updated_at' => now()],
            ['users_level' => 'kaprodi', 'created_at' => now(), 'updated_at' => now()],
            ['users_level' => 'dosen', 'created_at' => now(), 'updated_at' => now()],
            ['users_level' => 'mahasiswa', 'created_at' => now(), 'updated_at' => now()],
        ]);

        Schema::table('users', function (Blueprint $table) {
            $table->unsignedBigInteger('users_id')->nullable()->after('email');
        });

        $levelMap = DB::table('users_level')->pluck('users_id', 'users_level');

        DB::table('users')
            ->select(['id', 'role'])
            ->orderBy('id')
            ->get()
            ->each(function (object $user): void {
                DB::table('users')
                    ->where('id', $user->id)
                    ->update([
                        'users_id' => DB::table('users_level')->where('users_level', $user->role)->value('users_id'),
                    ]);
            });

        Schema::table('users', function (Blueprint $table) {
            $table->foreign('users_id')
                ->references('users_id')
                ->on('users_level')
                ->restrictOnDelete();

            $table->dropColumn('role');
        });

        if (DB::table('users')->whereNull('users_id')->exists()) {
            $mahasiswaLevelId = (int) $levelMap['mahasiswa'];

            DB::table('users')
                ->whereNull('users_id')
                ->update(['users_id' => $mahasiswaLevelId]);
        }

        Schema::table('users', function (Blueprint $table) {
            $table->unsignedBigInteger('users_id')->nullable(false)->change();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->enum('role', ['admin', 'mahasiswa', 'dosen', 'kaprodi'])->default('mahasiswa')->after('email');
        });

        $roleMap = DB::table('users_level')->pluck('users_level', 'users_id');

        DB::table('users')
            ->select(['id', 'users_id'])
            ->orderBy('id')
            ->get()
            ->each(function (object $user): void {
                DB::table('users')
                    ->where('id', $user->id)
                    ->update(['role' => DB::table('users_level')->where('users_id', $user->users_id)->value('users_level') ?? 'mahasiswa']);
            });

        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['users_id']);
            $table->dropColumn('users_id');
        });

        Schema::dropIfExists('users_level');
    }
};
