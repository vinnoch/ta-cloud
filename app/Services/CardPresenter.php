<?php

namespace App\Services;

use App\Models\Skripsi;
use App\Models\Periode;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CardPresenter
{
    public function forUser(?User $user): array
    {
        if (! $user) {
            return $this->globalCards();
        }

        return match ($user->role) {
            'kaprodi' => $this->kaprodiCards(),
            'dosen' => $this->dosenCards($user),
            'mahasiswa' => $this->mahasiswaCards($user),
            default => $this->globalCards(),
        };
    }

    private function kaprodiCards(): array
    {
        $activePeriode = Periode::query()
            ->where('status', 'ACTIVE')
            ->first();

        $periodeName = $activePeriode?->name ?? 'Tidak ada';

        $skripsiStats = DB::table('skripsis')
            ->select('current_phase', DB::raw('count(*) as total'))
            ->when($activePeriode, fn($q) => $q->where('periode_id', $activePeriode->id))
            ->groupBy('current_phase')
            ->pluck('total', 'current_phase')
            ->toArray();

        $totalSkripsi = array_sum($skripsiStats);
        $activeCount = $skripsiStats['proposal'] ?? 0;
        $sidangCount = ($skripsiStats['sidang_proposal'] ?? 0) + ($skripsiStats['sidang_skripsi'] ?? 0);

        return [
            [
                'eyebrow' => 'Periode Aktif',
                'title' => $periodeName,
                'subtitle' => 'Tahun akademik berjalan',
                'icon' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>',
                'badge' => 'AKTIF',
                'href' => route('kaprodi.periode.index'),
            ],
            [
                'eyebrow' => 'Statistik Skripsi',
                'title' => "{$totalSkripsi} Skripsi",
                'subtitle' => "{$activeCount} proposal, {$sidangCount} sidang",
                'icon' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>',
                'href' => route('kaprodi.skripsi.index'),
            ],
            [
                'eyebrow' => 'Dosen',
                'title' => User::query()->forRole('dosen')->count() . ' Dosen',
                'subtitle' => 'Terdaftar di sistem',
                'icon' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>',
                'href' => route('kaprodi.dosen.index'),
            ],
            [
                'eyebrow' => 'Mahasiswa',
                'title' => User::query()->forRole('mahasiswa')->count() . ' Mahasiswa',
                'subtitle' => 'Terdaftar di sistem',
                'icon' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>',
                'href' => route('kaprodi.mahasiswa.index'),
            ],
        ];
    }

    private function dosenCards(User $user): array
    {
        $assignedCount = DB::table('reviewer_assignments')
            ->where('lecturer_id', $user->id)
            ->count();

        $pendingGrades = DB::table('reviewer_assignments as ra')
            ->join('skripsis as s', 's.id', '=', 'ra.skripsi_id')
            ->leftJoin('grades as g', function ($join) use ($user) {
                $join->on('g.skripsi_id', '=', 's.id')
                    ->on('g.reviewer_id', '=', DB::raw((int) $user->id))
                    ->where('g.grade_event', '=', 'sidang_skripsi');
            })
            ->where('ra.lecturer_id', $user->id)
            ->whereIn('ra.role_type', ['pembimbing_1', 'pembimbing_2', 'penguji_1', 'penguji_2'])
            ->whereIn('s.current_phase', ['sidang_skripsi', 'revisi_sidang_skripsi'])
            ->whereNull('g.id')
            ->count();

        return [
            [
                'eyebrow' => 'Bimbingan',
                'title' => "{$assignedCount} Skripsi",
                'subtitle' => 'Sedang dibimbing',
                'icon' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>',
                'href' => route('dosen.skripsi.index'),
            ],
            [
                'eyebrow' => 'Penilaian',
                'title' => "{$pendingGrades} Menunggu",
                'subtitle' => 'Nilai belum final',
                'icon' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>',
                'href' => route('dosen.penilaian.index'),
            ],
        ];
    }

    private function mahasiswaCards(User $user): array
    {
        $skripsi = Skripsi::query()
            ->where('student_id', $user->id)
            ->whereNull('deleted_at')
            ->first();

        $periodeName = $skripsi?->periode?->name ?? '-';

        $cards = [];

        if ($skripsi) {
            $cards[] = [
                'eyebrow' => 'Skripsi Aktif',
                'title' => $skripsi->title,
                'subtitle' => "Periode: {$periodeName}",
                'badge' => strtoupper(str_replace('_', ' ', $skripsi->current_phase)),
                'icon' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/></svg>',
                'href' => route('mahasiswa.skripsi.show', $skripsi),
                'status' => 'active',
            ];
        } else {
            $cards[] = [
                'eyebrow' => 'Skripsi',
                'title' => 'Belum ada skripsi',
                'subtitle' => 'Buat skripsi baru untuk memulai',
                'icon' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/></svg>',
                'href' => route('mahasiswa.skripsi.create'),
                'status' => 'empty',
            ];
        }

        if ($skripsi) {
            $cards[] = [
                'eyebrow' => 'Bimbingan',
                'title' => 'Riwayat Bimbingan',
                'subtitle' => 'Lihat semua sesi bimbingan',
                'icon' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>',
                'href' => route('mahasiswa.skripsi.bimbingan.index', $skripsi),
            ];

            $cards[] = [
                'eyebrow' => 'Nilai',
                'title' => 'Nilai Akhir',
                'subtitle' => 'Lihat nilai akhir skripsi',
                'icon' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>',
                'href' => route('mahasiswa.skripsi.nilai.index', $skripsi),
            ];
        }

        return $cards;
    }

    private function globalCards(): array
    {
        return [
            [
                'eyebrow' => 'Welcome',
                'title' => 'TA Cloud',
                'subtitle' => 'Sistem Manajemen Tugas Akhir',
                'icon' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>',
                'href' => route('dashboard.index'),
            ],
        ];
    }
}
