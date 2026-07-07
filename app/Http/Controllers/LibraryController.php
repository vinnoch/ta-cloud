<?php

namespace App\Http\Controllers;

use App\Models\Skripsi;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Support\Str;

class LibraryController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));

        $skripsis = Skripsi::query()
            ->with(['student:id,name', 'student.level:id,name', 'documentVersions'])
            ->where('current_phase', 'skripsi_selesai')
            ->whereHas('documentVersions', fn ($query) => $query->where('phase', 'skripsi_final'))
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
                '<a class="text-link" href="' . route('library.show', $skripsi->id . '-' . Str::slug($skripsi->title), false) . '">Detail</a>',
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
            ->with(['student:id,name,nim', 'student.level:id,name', 'documentVersions'])
            ->whereKey($id)
            ->where('current_phase', 'skripsi_selesai')
            ->whereHas('documentVersions', fn ($query) => $query->where('phase', 'skripsi_final'))
            ->firstOrFail();

        $finalDocument = $skripsi->documentVersions
            ->where('phase', 'skripsi_final')
            ->sortByDesc('created_at')
            ->first();

        abort_if(! $finalDocument, 404);

        return view('library.show', [
            'title' => 'Detail Library Skripsi',
            'heading' => 'Detail Library Skripsi',
            'crumbs' => 'LIBRARY • DETAIL',
            'skripsi' => $skripsi,
            'finalDocument' => $finalDocument,
        ]);
    }
}
