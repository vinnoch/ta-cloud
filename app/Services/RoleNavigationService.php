<?php

namespace App\Services;

use App\Models\TugasAkhir;
use Illuminate\Support\Facades\Route;

class RoleNavigationService
{
    public function footerItems(): array
    {
        return [
            ['label' => 'Keluar', 'href' => '#', 'icon' => 'partials.icons.logout', 'danger' => true],
        ];
    }

    public function superadminNavItems(): array
    {
        return [
            ['label' => 'Dashboard', 'href' => route('superadmin.dashboard'), 'active' => 'superadmin.dashboard', 'icon' => 'partials.icons.dashboard-monitor'],
            ['label' => 'Users', 'href' => route('superadmin.users.index'), 'active' => 'superadmin.users.*', 'icon' => 'partials.icons.grid'],
            ['label' => 'Pendaftaran', 'href' => route('registrations.index'), 'active' => 'registrations.*', 'icon' => 'partials.icons.clipboard'],
            ['label' => 'Skripsi', 'href' => route('superadmin.skripsi.index'), 'active' => 'superadmin.skripsi.*', 'icon' => 'partials.icons.file'],
            ['label' => 'Non-Skripsi', 'href' => route('superadmin.non-skripsi.index'), 'active' => 'superadmin.non-skripsi.*', 'icon' => 'partials.icons.file-plain'],
            ['label' => 'Setting', 'href' => route('superadmin.settings.edit'), 'active' => 'superadmin.settings.*', 'icon' => 'partials.icons.edit'],
            ['label' => 'Log Sistem', 'href' => route('superadmin.audit.index'), 'active' => 'superadmin.audit.*', 'icon' => 'partials.icons.phase-shield'],
        ];
    }

    public function kaprodiNavItems(): array
    {
        return [
            ['label' => 'Dashboard', 'href' => route('kaprodi.dashboard'), 'active' => 'kaprodi.dashboard', 'icon' => 'partials.icons.dashboard-monitor'],
            ['label' => 'Pendaftaran', 'href' => route('registrations.index'), 'active' => 'registrations.*', 'icon' => 'partials.icons.clipboard'],
            [
                'label' => 'Monitoring',
                'href' => route('kaprodi.tugas-akhir.index'),
                'active' => ['kaprodi.tugas-akhir.*', 'kaprodi.skripsi.*', 'kaprodi.non-skripsi.*', 'kaprodi.proposal-submissions.*', 'kaprodi.sidang-requests.*', 'kaprodi.final-reviews.*'],
                'icon' => 'partials.icons.eye',
                'children' => [
                    ['label' => 'Tugas Akhir', 'href' => route('kaprodi.tugas-akhir.index'), 'active' => ['kaprodi.tugas-akhir.*', 'kaprodi.skripsi.*', 'kaprodi.non-skripsi.*'], 'icon' => 'partials.icons.file'],
                    ['label' => 'Pengajuan Proposal', 'href' => route('kaprodi.proposal-submissions.index'), 'active' => 'kaprodi.proposal-submissions.*', 'icon' => 'partials.icons.clipboard'],
                    ['label' => 'Permohonan Sidang', 'href' => route('kaprodi.sidang-requests.index'), 'active' => 'kaprodi.sidang-requests.*', 'icon' => 'partials.icons.phase-flag'],
                    ['label' => 'Dokumen Final', 'href' => route('kaprodi.final-reviews.index'), 'active' => 'kaprodi.final-reviews.*', 'icon' => 'partials.icons.phase-flag'],
                ],
            ],
            [
                'label' => 'Master Data',
                'href' => route('kaprodi.dosen.index'),
                'active' => ['kaprodi.dosen.*', 'kaprodi.mahasiswa.*', 'kaprodi.tahun-akademik.*', 'kaprodi.periode.*', 'kaprodi.formats.*', 'kaprodi.document-templates.*'],
                'icon' => 'partials.icons.folder',
                'children' => [
                    ['label' => 'Master Dosen', 'href' => route('kaprodi.dosen.index'), 'active' => 'kaprodi.dosen.*', 'icon' => 'partials.icons.folder'],
                    ['label' => 'Master Mahasiswa', 'href' => route('kaprodi.mahasiswa.index'), 'active' => 'kaprodi.mahasiswa.*', 'icon' => 'partials.icons.folder'],
                    ['label' => 'Tahun Akademik', 'href' => route('kaprodi.tahun-akademik.index'), 'active' => 'kaprodi.tahun-akademik.*', 'icon' => 'partials.icons.phase-flag'],
                    ['label' => 'Periode', 'href' => route('kaprodi.periode.index'), 'active' => 'kaprodi.periode.*', 'icon' => 'partials.icons.clipboard'],
                    ['label' => 'Format Nilai', 'href' => route('kaprodi.formats.index'), 'active' => ['kaprodi.formats.index', 'kaprodi.formats.show', 'kaprodi.formats.edit', 'kaprodi.formats.create'], 'icon' => 'partials.icons.clipboard'],
                    ['label' => 'List Dokumen Final', 'href' => route('kaprodi.document-templates.index'), 'active' => 'kaprodi.document-templates.*', 'icon' => 'partials.icons.file-plain'],
                ],
            ],
            ['label' => 'Nilai', 'href' => route('kaprodi.nilai.index'), 'active' => ['kaprodi.nilai.*', 'kaprodi.formats.grades.show'], 'icon' => 'partials.icons.clipboard'],
            ['label' => 'Import Dosen', 'href' => route('kaprodi.import.dosen'), 'active' => 'kaprodi.import.dosen', 'icon' => 'partials.icons.plus'],
            ['label' => 'Import Mahasiswa', 'href' => route('kaprodi.import.mahasiswa'), 'active' => 'kaprodi.import.mahasiswa', 'icon' => 'partials.icons.plus'],
            ['label' => 'Export Skripsi', 'href' => route('kaprodi.skripsi.export.page'), 'active' => 'kaprodi.skripsi.export.page', 'icon' => 'partials.icons.download'],
        ];
    }

    public function dosenNavItems(): array
    {
        return [
            ['label' => 'Dashboard', 'href' => route('dosen.dashboard'), 'active' => 'dosen.dashboard', 'icon' => 'partials.icons.dashboard-monitor'],
            ['label' => 'Proposal', 'href' => route('dosen.proposal.index'), 'active' => 'dosen.proposal.*', 'icon' => 'partials.icons.clipboard'],
            ['label' => 'Tugas Akhir', 'href' => route('dosen.tugas-akhir.index'), 'active' => ['dosen.tugas-akhir.*', 'dosen.skripsi.*', 'dosen.non-skripsi.*'], 'icon' => 'partials.icons.file'],
            ['label' => 'Pengajuan Sidang', 'href' => route('dosen.sidang-request.index'), 'active' => 'dosen.sidang-request.*', 'icon' => 'partials.icons.phase-flag'],
            ['label' => 'Penilaian & Revisi', 'href' => route('dosen.penilaian.index'), 'active' => 'dosen.penilaian.*', 'icon' => 'partials.icons.clipboard'],
        ];
    }

    public function mahasiswaNavItems(?int $userId = null, ?int $skripsiId = null): array
    {
        $items = [];

        $resolvedSkripsiId = $skripsiId;

        if (! $resolvedSkripsiId && $userId && Route::has('mahasiswa.skripsi.bimbingan.index')) {
            $resolvedSkripsiId = TugasAkhir::query()->where('student_id', $userId)->value('id');
        }

        $resolvedTugasAkhir = $resolvedSkripsiId
            ? TugasAkhir::query()->select(['id', 'type', 'current_phase'])->find($resolvedSkripsiId)
            : null;
        $registrationHref = $resolvedTugasAkhir
            ? app(TugasAkhirRouteService::class)->route('mahasiswa', $resolvedTugasAkhir, 'index')
            : route('mahasiswa.pendaftaran.create');

        $items[] = [
            'label' => 'Pendaftaran',
            'href' => $registrationHref,
            'active' => ['mahasiswa.pendaftaran.*', 'mahasiswa.skripsi.index', 'mahasiswa.skripsi.create', 'mahasiswa.skripsi.store', 'mahasiswa.non-skripsi.index', 'mahasiswa.non-skripsi.create', 'mahasiswa.non-skripsi.store'],
            'icon' => 'partials.icons.clipboard',
        ];

        if ($resolvedSkripsiId) {
            $resolvedSkripsi = $resolvedTugasAkhir;
            $routePrefix = $resolvedSkripsi?->type === 'non_skripsi' ? 'mahasiswa.non-skripsi' : 'mahasiswa.skripsi';
            $canAccessProgressMenus = $resolvedSkripsi
                && ! in_array($resolvedSkripsi->current_phase, ['proposal', 'sidang_proposal'], true);

            if ($canAccessProgressMenus && Route::has($routePrefix.'.bimbingan.index')) {
                $items[] = ['label' => 'Bimbingan', 'href' => route($routePrefix.'.bimbingan.index', $resolvedSkripsiId), 'active' => [$routePrefix.'.bimbingan.*'], 'icon' => 'partials.icons.folder'];
            }

            if ($canAccessProgressMenus && Route::has($routePrefix.'.nilai.index')) {
                $items[] = ['label' => 'Nilai', 'href' => route($routePrefix.'.nilai.index', $resolvedSkripsiId), 'active' => [$routePrefix.'.nilai.*'], 'icon' => 'partials.icons.phase-flag'];
            }

            if (
                $resolvedSkripsi
                && Route::has($routePrefix.'.final.skripsi.index')
                && in_array($resolvedSkripsi->current_phase, ['revisi_sidang_skripsi', 'review_dokumen_final', 'skripsi_selesai'], true)
            ) {
                $items[] = ['label' => 'Dokumen Final', 'href' => route($routePrefix.'.final.skripsi.index', $resolvedSkripsiId), 'active' => [$routePrefix.'.final.skripsi.*'], 'icon' => 'partials.icons.file-plain'];
            }
        }

        return $items;
    }
}
