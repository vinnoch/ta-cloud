<?php

namespace App\Http\Controllers\Kaprodi;

use App\Http\Controllers\Controller;
use App\Models\Skripsi;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NonSkripsiController extends Controller
{
    use BuildsKaprodiPage;

    public function index(Request $request): View|JsonResponse
    {
        $search = trim((string) $request->query('q', ''));
        $sort = (string) $request->query('sort', 'judul');
        $direction = strtolower((string) $request->query('direction', 'desc')) === 'asc' ? 'asc' : 'desc';

        if (! in_array($sort, ['judul', 'mahasiswa', 'link'], true)) {
            $sort = 'judul';
        }

        $query = Skripsi::query()
            ->select('skripsis.*')
            ->leftJoin('users as students_sort', 'students_sort.id', '=', 'skripsis.student_id')
            ->leftJoin('non_skripsi_records as non_skripsi_sort', 'non_skripsi_sort.skripsi_id', '=', 'skripsis.id')
            ->where('skripsis.type', 'non_skripsi')
            ->with(['student', 'periode.tahunAkademik', 'nonSkripsiRecord'])
            ->when($search !== '', function ($builder) use ($search): void {
                $builder->where(function ($inner) use ($search): void {
                    $inner->where('skripsis.title', 'like', "%{$search}%")
                        ->orWhere('students_sort.name', 'like', "%{$search}%")
                        ->orWhere('students_sort.nim', 'like', "%{$search}%")
                        ->orWhere('skripsis.journal_article_url', 'like', "%{$search}%")
                        ->orWhere('non_skripsi_sort.publication_url', 'like', "%{$search}%");
                });
            });

        $sortMap = [
            'judul' => 'skripsis.title',
            'mahasiswa' => 'students_sort.name',
            'link' => 'skripsis.journal_article_url',
        ];

        $nonSkripsis = $query
            ->orderBy($sortMap[$sort], $direction)
            ->orderBy('skripsis.id', 'desc')
            ->paginate(10)
            ->withQueryString();

        if ($request->ajax() || $request->expectsJson()) {
            return response()->json([
                'table_html' => view('kaprodi.non-skripsi.partials.table', [
                    'nonSkripsis' => $nonSkripsis,
                    'sort' => $sort,
                    'direction' => $direction,
                ])->render(),
                'pagination_html' => view('kaprodi.non-skripsi.partials.pagination', [
                    'nonSkripsis' => $nonSkripsis,
                ])->render(),
                'count_text' => $nonSkripsis->total() . ' non-skripsi ditemukan.',
            ]);
        }

        return view('kaprodi.non-skripsi.index', $this->kaprodiPage('Monitoring Non-Skripsi', 'KAPRODI • NON-SKRIPSI', [
            'nonSkripsis' => $nonSkripsis,
            'search' => $search,
            'sort' => $sort,
            'direction' => $direction,
        ]));
    }

    public function show(Skripsi $skripsi): View
    {
        abort_unless($skripsi->type === 'non_skripsi', 404);

        $skripsi->load(['student', 'periode.tahunAkademik', 'nonSkripsiRecord']);

        return view('kaprodi.non-skripsi.show', $this->kaprodiPage('Detail Non-Skripsi', 'KAPRODI • NON-SKRIPSI', [
            'skripsi' => $skripsi,
            'record' => $skripsi->nonSkripsiRecord,
        ]));
    }
}
