<?php

namespace App\Services;

use App\Models\Skripsi;
use App\Models\User;
use Illuminate\Support\Collection;

class MahasiswaSkripsiDataService
{
    public function activeForUser(User $user): ?Skripsi
    {
        return Skripsi::query()
            ->where('student_id', $user->id)
            ->whereNull('deleted_at')
            ->where('current_phase', '!=', 'skripsi_selesai')
            ->with([
                'student',
                'periode',
                'assignments.lecturer',
                'bimbingans.reviewer',
                'documentVersions' => fn ($query) => $query->latest('created_at'),
            ])
            ->latest()
            ->first();
    }

    public function latestBimbingans(Skripsi $skripsi, int $limit = 5): Collection
    {
        return $skripsi->bimbingans
            ->sortByDesc(fn ($item) => $item->meeting_date?->timestamp ?? 0)
            ->take($limit)
            ->values();
    }

    public function reviewers(Skripsi $skripsi): Collection
    {
        return $skripsi->assignments
            ->map(function ($assignment) {
                return [
                    'role' => str($assignment->role_type)->replace('_', ' ')->title()->toString(),
                    'name' => $assignment->lecturer?->name ?? '-',
                ];
            })
            ->values();
    }

    public function documents(Skripsi $skripsi, int $limit = 5): Collection
    {
        return $skripsi->documentVersions
            ->sortByDesc(fn ($item) => $item->created_at?->timestamp ?? 0)
            ->take($limit)
            ->values();
    }
}
