<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function () {
            $keeper = DB::table('periodes')
                ->where(function ($query) {
                    $query->where('is_aktif', true)
                        ->orWhere('status', 'active');
                })
                ->orderByDesc('updated_at')
                ->orderByDesc('id')
                ->first();

            DB::table('periodes')
                ->where(function ($query) {
                    $query->where('is_aktif', true)
                        ->orWhere('status', 'active');
                })
                ->update([
                    'is_aktif' => false,
                    'status' => 'closed',
                ]);

            if ($keeper) {
                DB::table('periodes')
                    ->where('id', $keeper->id)
                    ->update([
                        'is_aktif' => true,
                        'status' => 'active',
                    ]);
            }
        });
    }

    public function down(): void
    {
    }
};
