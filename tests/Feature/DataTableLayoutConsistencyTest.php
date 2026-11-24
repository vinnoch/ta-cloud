<?php

use Illuminate\Support\Facades\File;

it('keeps administrative data table pages on the canonical list layout', function (string $view) {
    $markup = File::get(resource_path("views/{$view}.blade.php"));

    expect($markup)
        ->toContain('<section class="card')
        ->toContain('<div class="section-heading')
        ->toContain('<h1 class="acss-page-title"')
        ->not->toContain('<section class="acss-crud-card');
})->with([
    'superadmin users' => 'superadmin/users/index',
    'superadmin tugas akhir' => 'superadmin/tugas-akhir/index',
    'superadmin audit log' => 'superadmin/audit',
    'registration review' => 'kaprodi/registrations/index',
    'dosen assessment queue' => 'dosen/antrian/index',
    'dosen proposal' => 'dosen/proposal/index',
    'dosen tugas akhir' => 'dosen/skripsi/index',
    'dosen sidang request' => 'dosen/sidang-request/index',
    'dosen grading' => 'dosen/penilaian/index',
    'kaprodi dosen' => 'kaprodi/dosen/index',
    'kaprodi mahasiswa' => 'kaprodi/mahasiswa/index',
    'kaprodi periode' => 'kaprodi/periode/index',
    'kaprodi tahun akademik' => 'kaprodi/tahun-akademik/index',
    'kaprodi document templates' => 'kaprodi/document-templates/index',
    'kaprodi assessment formats' => 'kaprodi/format-penilaian/index',
    'kaprodi final review' => 'kaprodi/final-review/index',
    'kaprodi grades' => 'kaprodi/nilai/index',
    'kaprodi proposal submission' => 'kaprodi/proposal-submission/index',
    'kaprodi sidang request' => 'kaprodi/sidang-request/index',
    'kaprodi tugas akhir' => 'kaprodi/skripsi/index',
    'kaprodi library publishing' => 'kaprodi/library/index',
]);

it('mirrors AJAX pagination above each filtered data table', function () {
    $markup = File::get(resource_path('views/partials/ajax-list-script.blade.php'));

    expect($markup)
        ->toContain('form.insertAdjacentElement(\'afterend\', topPaginationWrapper)')
        ->toContain('topPaginationWrapper.innerHTML = paginationWrapper.innerHTML')
        ->toContain("topPaginationWrapper?.addEventListener('click', handlePaginationClick)");
});

it('keeps table result counts out of headings', function () {
    $offendingViews = collect(File::allFiles(resource_path('views')))
        ->filter(function ($file): bool {
            $markup = File::get($file->getPathname());

            return preg_match('/<p[^>]*count-text[^>]*>/i', $markup) === 1
                || preg_match('/<p[^>]*acss-muted[^>]*>[^<]*(?:->total\(\)|->count\(\))/i', $markup) === 1;
        })
        ->map(fn ($file): string => $file->getRelativePathname())
        ->values()
        ->all();

    expect($offendingViews)->toBe([]);
});
