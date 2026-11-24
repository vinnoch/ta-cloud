<?php

use App\Models\TugasAkhir;

it('renders Dosen and Kaprodi phase totals as native filter buttons', function () {
    $chartData = [
        ['label' => 'Proposal', 'filter' => 'proposal', 'value' => 3],
        ['label' => 'Bimbingan', 'filter' => 'bimbingan_skripsi', 'value' => 2],
    ];

    $dosen = view('dosen.skripsi.partials.stats', compact('chartData'))->render();
    $kaprodi = view('kaprodi.skripsi.partials.stats', compact('chartData'))->render();

    expect($dosen)
        ->toContain('<button type="button" class="stat-card acss-stat-card-inline"')
        ->not->toContain('<div class="stat-card acss-stat-card-inline"')
        ->and($kaprodi)
        ->toContain('<button type="button" class="stat-card acss-stat-card-inline"')
        ->not->toContain('<div class="stat-card acss-stat-card-inline"');
});

it('keeps every thesis phase and state in the compact shared timeline', function () {
    $skripsi = new TugasAkhir([
        'type' => 'skripsi',
        'current_phase' => 'bimbingan_skripsi',
    ]);

    $html = view('partials.skripsi-phase-timeline', [
        'skripsiTimelineRecord' => $skripsi,
    ])->render();

    expect(substr_count($html, 'acss-phase-lane '))->toBe(7)
        ->and($html)->toContain('Proposal')
        ->toContain('Bimbingan Skripsi')
        ->toContain('Dokumen Final')
        ->toContain('Skripsi Selesai')
        ->toContain('is-current');
});
