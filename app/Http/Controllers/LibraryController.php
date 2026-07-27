<?php

namespace App\Http\Controllers;

use App\Models\Skripsi;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;
use Illuminate\View\View;

class LibraryController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));

        $skripsis = Skripsi::query()
            ->with([
                'student:id,name',
                'student.level:id,name',
                'documentVersions',
                'documentSubmissions.templateItem',
                'documentSubmissions.documentVersion',
            ])
            ->where('current_phase', 'skripsi_selesai')
            ->where(function (Builder $query): void {
                $query->whereHas('documentVersions', fn ($documentQuery) => $documentQuery->where('phase', 'skripsi_final'))
                    ->orWhereHas('documentSubmissions', function (Builder $submissionQuery): void {
                        $submissionQuery->where(function (Builder $inner): void {
                            $inner->whereNotNull('document_version_id')
                                ->orWhereNotNull('notes');
                        });
                    });
            })
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($inner) use ($search) {
                    $inner->where('title', 'like', "%{$search}%")
                        ->orWhereHas('student', fn ($studentQuery) => $studentQuery->where('name', 'like', "%{$search}%"));
                });
            })
            ->latest('id')
            ->paginate(12)
            ->withQueryString();

        $rows = $skripsis->getCollection()->map(function (Skripsi $skripsi) {
            return [
                $skripsi->title,
                $skripsi->student?->name ?? '-',
                $skripsi->student?->level?->name ?? 'Sistem Informasi',
                new HtmlString('<a class="text-link" href="'.route('library.show', $skripsi->id.'-'.Str::slug($skripsi->title), false).'">Detail</a>'),
            ];
        })->all();

        return view('library.index', [
            'title' => 'Library Skripsi',
            'heading' => 'Library Skripsi',
            'crumbs' => 'LIBRARY • INDEX',
            'libraryStats' => [
                ['label' => 'Total Skripsi', 'value' => (string) $skripsis->total()],
                ['label' => 'Tersedia', 'value' => (string) $skripsis->count()],
            ],
            'rows' => $rows,
            'filters' => [
                ['eyebrow' => 'Filter', 'title' => 'Pencarian', 'description' => $search !== '' ? $search : 'Semua judul'],
            ],
            'skripsis' => $skripsis,
            'search' => $search,
        ]);
    }

    public function show(string $slug): View
    {
        $id = (int) Str::before($slug, '-');

        $skripsi = Skripsi::query()
            ->with([
                'student:id,name,nim',
                'student.level:id,name',
                'documentVersions',
                'documentSubmissions.templateItem',
                'documentSubmissions.documentVersion',
            ])
            ->whereKey($id)
            ->where('current_phase', 'skripsi_selesai')
            ->where(function (Builder $query): void {
                $query->whereHas('documentVersions', fn ($documentQuery) => $documentQuery->where('phase', 'skripsi_final'))
                    ->orWhereHas('documentSubmissions', function (Builder $submissionQuery): void {
                        $submissionQuery->where(function (Builder $inner): void {
                            $inner->whereNotNull('document_version_id')
                                ->orWhereNotNull('notes');
                        });
                    });
            })
            ->firstOrFail();

        $finalDocument = $skripsi->documentVersions
            ->where('phase', 'skripsi_final')
            ->sortByDesc('created_at')
            ->first();

        $libraryDocuments = $skripsi->documentSubmissions
            ->filter(fn ($submission) => $submission->documentVersion || filled($submission->notes))
            ->sortBy(fn ($submission) => [
                $submission->templateItem?->sort_order ?? PHP_INT_MAX,
                $submission->id,
            ])
            ->values();

        abort_if(! $finalDocument && $libraryDocuments->isEmpty(), 404);

        return view('library.show', [
            'title' => 'Detail Library Skripsi',
            'heading' => 'Detail Library Skripsi',
            'crumbs' => 'LIBRARY • DETAIL',
            'skripsi' => $skripsi,
            'finalDocument' => $finalDocument,
            'libraryDocuments' => $libraryDocuments,
        ]);
    }
}
