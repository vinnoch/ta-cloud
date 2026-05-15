<?php

namespace App\Http\Controllers\Kaprodi;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class NilaiController extends Controller
{
    use BuildsKaprodiPage;

    public function index(Request $request): View|JsonResponse
    {
        $search = $request->string('q')->toString();
        $sort = $request->string('sort')->toString();
        $direction = strtolower($request->string('direction')->toString()) === 'asc' ? 'asc' : 'desc';
        $skripsiId = $request->integer('skripsi_id');

        $nilai = DB::table('grades as g')
            ->join('skripsis as s', 's.id', '=', 'g.skripsi_id')
            ->join('users as students', 'students.id', '=', 's.student_id')
            ->leftJoin('users as reviewers', 'reviewers.id', '=', 'g.reviewer_id')
            ->whereIn('g.grade_event', ['sidang_proposal', 'sidang_skripsi'])
            ->when($skripsiId > 0, fn ($query) => $query->where('g.skripsi_id', $skripsiId))
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($innerQuery) use ($search): void {
                    $innerQuery->where('students.name', 'like', "%{$search}%")
                        ->orWhere('students.nim', 'like', "%{$search}%")
                        ->orWhere('s.title', 'like', "%{$search}%")
                        ->orWhere('reviewers.name', 'like', "%{$search}%");
                });
            })
            ->select([
                'g.id',
                'g.skripsi_id',
                'g.format_penilaian_id',
                'g.grade_event',
                'g.status',
                'g.score',
                'g.role_type',
                'students.name as student_name',
                'students.nim as nim',
                's.title as title',
                's.current_phase as current_phase',
                'reviewers.name as reviewer_name',
                'g.updated_at as last_added_at',
            ])
            ->when($sort === 'mahasiswa', fn ($query) => $query->orderBy('student_name', $direction))
            ->when($sort === 'judul', fn ($query) => $query->orderBy('title', $direction))
            ->when($sort === 'dosen', fn ($query) => $query->orderBy('reviewer_name', $direction))
            ->when($sort === 'fase', fn ($query) => $query->orderBy('g.grade_event', $direction))
            ->when($sort === 'nilai', fn ($query) => $query->orderBy('g.score', $direction))
            ->when(! in_array($sort, ['mahasiswa', 'judul', 'dosen', 'fase', 'nilai'], true), fn ($query) => $query->orderByDesc('last_added_at'))
            ->paginate(10)
            ->withQueryString();

        if ($request->ajax() || $request->expectsJson()) {
            return response()->json([
                'table_html' => view('kaprodi.nilai.partials.table', ['data_nilai' => $nilai, 'sort' => $sort, 'direction' => $direction])->render(),
                'pagination_html' => view('kaprodi.nilai.partials.pagination', ['data_nilai' => $nilai])->render(),
                'count_text' => $nilai->total() . ' nilai ditemukan.',
            ]);
        }

        return view('kaprodi.nilai.index', $this->page('Rekap Nilai', 'KAPRODI • NILAI', [
            'data_nilai' => $nilai,
            'search' => $search,
            'sort' => $sort,
            'direction' => $direction,
            'sideCards' => [],
            'skripsiId' => $skripsiId,
        ]));
    }

    private function page(string $heading, string $crumbs, array $extra = []): array
    {
        return $this->kaprodiPage($heading, $crumbs, $extra);
    }
}
