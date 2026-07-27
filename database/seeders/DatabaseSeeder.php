<?php

namespace Database\Seeders;

use App\Models\UserLevel;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        foreach (['superadmin', 'admin', 'kaprodi', 'dosen', 'mahasiswa'] as $role) {
            UserLevel::query()->updateOrCreate(['users_level' => $role]);
        }

        DB::table('departments')->updateOrInsert(
            ['code' => 'FTI'],
            ['name' => 'Fakultas Teknologi Informasi', 'updated_at' => now(), 'created_at' => now()],
        );

        $departmentId = DB::table('departments')->where('code', 'FTI')->value('id');

        foreach ([
            ['code' => 'SI', 'name' => 'Sistem Informasi'],
            ['code' => 'TI', 'name' => 'Teknik Informatika'],
        ] as $program) {
            DB::table('study_programs')->updateOrInsert(
                ['code' => $program['code']],
                [
                    'department_id' => $departmentId,
                    'name' => $program['name'],
                    'degree_level' => 'S1',
                    'is_active' => true,
                    'updated_at' => now(),
                    'created_at' => now(),
                ],
            );
        }
    }
}
