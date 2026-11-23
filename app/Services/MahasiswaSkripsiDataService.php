<?php

namespace App\Services;

use App\Models\TugasAkhir;
use App\Models\User;
use Illuminate\Support\Collection;

class MahasiswaSkripsiDataService
{
    public function activeForUser(User $user, ?string $type = null): ?TugasAkhir
    {
        return TugasAkhir::query()
            ->where('student_id', $user->id)
            ->when($type, fn ($query) => $query->where('type', $type))
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

    public function latestBimbingans(TugasAkhir $skripsi, int $limit = 5): Collection
    {
        return $skripsi->bimbingans
            ->sortByDesc(fn ($item) => $item->meeting_date?->timestamp ?? 0)
            ->take($limit)
            ->values();
    }

    public function reviewers(TugasAkhir $skripsi): Collection
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

    public function documents(TugasAkhir $skripsi, int $limit = 5): Collection
    {
        return $skripsi->documentVersions
            ->sortByDesc(fn ($item) => $item->created_at?->timestamp ?? 0)
            ->take($limit)
            ->values();
    }
}
