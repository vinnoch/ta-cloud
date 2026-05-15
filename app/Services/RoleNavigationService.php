<?php

namespace App\Services;

use App\Models\Skripsi;
use Illuminate\Support\Facades\Route;

class RoleNavigationService
{
    public function footerItems(): array
    {
        return [
                        ['label' => 'Keluar', 'href' => '#', 'icon' => 'partials.icons.logout', 'danger' => true],
        ];
    }

    public function kaprodiNavItems(): array
    {
        return [
            ['label' => 'Dashboard', 'href' => route('kaprodi.dashboard'), 'active' => 'kaprodi.dashboard', 'icon' => 'partials.icons.grid'],
            [
                'label' => 'Monitoring',
                'href' => route('kaprodi.skripsi.index'),
                'active' => ['kaprodi.skripsi.index', 'kaprodi.proposal-submissions.*', 'kaprodi.sidang-requests.*', 'kaprodi.final-reviews.*'],
                'icon' => 'partials.icons.search',
                'children' => [
                    ['label' => 'Skripsi', 'href' => route('kaprodi.skripsi.index'), 'active' => 'kaprodi.skripsi.index', 'icon' => 'partials.icons.file'],
                    ['label' => 'Pengajuan Proposal', 'href' => route('kaprodi.proposal-submissions.index'), 'active' => 'kaprodi.proposal-submissions.*', 'icon' => 'partials.icons.clipboard'],
                    ['label' => 'Permohonan Sidang', 'href' => route('kaprodi.sidang-requests.index'), 'active' => 'kaprodi.sidang-requests.*', 'icon' => 'partials.icons.phase-flag'],
                    ['label' => 'Review Dokumen Final', 'href' => route('kaprodi.final-reviews.index'), 'active' => 'kaprodi.final-reviews.*', 'icon' => 'partials.icons.phase-flag'],
                ],
            ],
            [
                'label' => 'Master Data',
                'href' => route('kaprodi.dosen.index'),
                'active' => ['kaprodi.dosen.*', 'kaprodi.mahasiswa.*', 'kaprodi.tahun-akademik.*', 'kaprodi.periode.*', 'kaprodi.formats.*'],
                'icon' => 'partials.icons.database',
                'children' => [
                    ['label' => 'Master Dosen', 'href' => route('kaprodi.dosen.index'), 'active' => 'kaprodi.dosen.*', 'icon' => 'partials.icons.chat'],
                    ['label' => 'Master Mahasiswa', 'href' => route('kaprodi.mahasiswa.index'), 'active' => 'kaprodi.mahasiswa.*', 'icon' => 'partials.icons.folder'],
                    ['label' => 'Tahun Akademik', 'href' => route('kaprodi.tahun-akademik.index'), 'active' => 'kaprodi.tahun-akademik.*', 'icon' => 'partials.icons.phase-flag'],
                    ['label' => 'Periode', 'href' => route('kaprodi.periode.index'), 'active' => 'kaprodi.periode.*', 'icon' => 'partials.icons.clipboard'],
                    ['label' => 'Format Nilai', 'href' => route('kaprodi.formats.index'), 'active' => ['kaprodi.formats.index', 'kaprodi.formats.show', 'kaprodi.formats.edit', 'kaprodi.formats.create'], 'icon' => 'partials.icons.clipboard'],
                ],
            ],
            ['label' => 'Nilai', 'href' => route('kaprodi.nilai.index'), 'active' => ['kaprodi.nilai.*', 'kaprodi.formats.grades.show'], 'icon' => 'partials.icons.clipboard'],
            ['label' => 'Import Dosen', 'href' => route('kaprodi.import.dosen'), 'active' => 'kaprodi.import.dosen', 'icon' => 'partials.icons.download'],
            ['label' => 'Import Mahasiswa', 'href' => route('kaprodi.import.mahasiswa'), 'active' => 'kaprodi.import.mahasiswa', 'icon' => 'partials.icons.download'],
        ];
    }

    public function dosenNavItems(): array
    {
        return [
            ['label' => 'Dashboard', 'href' => route('dosen.dashboard'), 'active' => 'dosen.dashboard', 'icon' => 'partials.icons.grid'],
            ['label' => 'Skripsi', 'href' => route('dosen.skripsi.index'), 'active' => 'dosen.skripsi.*', 'icon' => 'partials.icons.file'],
            ['label' => 'Pengajuan Sidang', 'href' => route('dosen.sidang-request.index'), 'active' => 'dosen.sidang-request.*', 'icon' => 'partials.icons.phase-flag'],
            ['label' => 'Penilaian', 'href' => route('dosen.penilaian.index'), 'active' => 'dosen.penilaian.*', 'icon' => 'partials.icons.clipboard'],
        ];
    }

    public function mahasiswaNavItems(?int $userId = null, ?int $skripsiId = null): array
    {
        $items = [
            ['label' => 'Dashboard', 'href' => route('mahasiswa.dashboard'), 'active' => 'mahasiswa.dashboard', 'icon' => 'partials.icons.grid'],
        ];

        $resolvedSkripsiId = $skripsiId;

        if (! $resolvedSkripsiId && $userId && Route::has('mahasiswa.skripsi.bimbingan.index')) {
            $resolvedSkripsiId = Skripsi::query()->where('student_id', $userId)->value('id');
        }

        $items[] = ['label' => 'Tugas Akhir', 'href' => route('mahasiswa.skripsi.index'), 'active' => ['mahasiswa.skripsi.index', 'mahasiswa.skripsi.show', 'mahasiswa.skripsi.create', 'mahasiswa.skripsi.edit'], 'icon' => 'partials.icons.file'];

        if ($resolvedSkripsiId) {
            if (Route::has('mahasiswa.skripsi.bimbingan.index')) {
                $items[] = ['label' => 'Bimbingan', 'href' => route('mahasiswa.skripsi.bimbingan.index', $resolvedSkripsiId), 'active' => 'mahasiswa.skripsi.bimbingan.*', 'icon' => 'partials.icons.chat'];
            }

            if (Route::has('mahasiswa.skripsi.nilai.index')) {
                $items[] = ['label' => 'Nilai', 'href' => route('mahasiswa.skripsi.nilai.index', $resolvedSkripsiId), 'active' => 'mahasiswa.skripsi.nilai.*', 'icon' => 'partials.icons.phase-flag'];
            }
        }

        return $items;
    }
}