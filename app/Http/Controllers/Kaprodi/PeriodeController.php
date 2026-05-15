<?php

namespace App\Http\Controllers\Kaprodi;

use App\Http\Controllers\Controller;
use App\Models\Periode;
use App\Models\TahunAkademik;
use App\Models\Skripsi;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PeriodeController extends Controller
{
    use BuildsKaprodiPage;
    public function index(Request $request): View|\Illuminate\Http\JsonResponse
    {
        $search = $request->string('q')->toString();
        $sort = $request->string('sort')->toString();
        $direction = strtolower($request->string('direction')->toString()) === 'asc' ? 'asc' : 'desc';

        $normalizedSearch = strtolower(trim($search));

        $periode = Periode::query()
            ->with('tahunAkademik')
            ->withExists(['skripsis as has_skripsis', 'formats as has_formats'])
            ->when($search !== '', function ($query) use ($search, $normalizedSearch): void {
                $query->where(function ($query) use ($search, $normalizedSearch): void {
                    $query->where('kode_periode', 'like', "%{$search}%")
                        ->orWhereHas('tahunAkademik', function ($tahunAkademikQuery) use ($search): void {
                            $tahunAkademikQuery->where('tahun_awal', 'like', "%{$search}%")->orWhere('tahun_akhir', 'like', "%{$search}%");
                        });

                    if (str_contains($normalizedSearch, 'ganjil')) {
                        $query->orWhere('semester', 1);
                    }

                    if (str_contains($normalizedSearch, 'genap')) {
                        $query->orWhere('semester', 2);
                    }
                });
            })
            ->when($sort === 'kode', fn ($query) => $query->orderBy('kode_periode', $direction))
            ->when($sort === 'status', fn ($query) => $query->orderBy('status', $direction))
            ->when($sort === 'tahun', function ($query) use ($direction) {
                $query->leftJoin('tahun_akademiks', 'tahun_akademiks.id', '=', 'periodes.tahun_akademik_id')
                    ->orderBy('tahun_akademiks.tahun_awal', $direction)->orderBy('periodes.semester', $direction)
                    ->select('periodes.*');
            })
            ->when(! in_array($sort, ['kode', 'status', 'tahun'], true), fn ($query) => $query->orderByDesc('created_at'))
            ->paginate(10)
            ->withQueryString();

        if ($request->ajax() || $request->expectsJson()) {
            return response()->json([
                'table_html' => view('kaprodi.periode.partials.table', ['periode' => $periode, 'sort' => $sort, 'direction' => $direction])->render(),
                'pagination_html' => view('kaprodi.periode.partials.pagination', ['periode' => $periode])->render(),
                'count_text' => $periode->total() . ' periode akademik tersedia.',
            ]);
        }

        $tahunAkademiks = TahunAkademik::orderByDesc('tahun_awal')->get();

        return view('kaprodi.periode.index', $this->page('Master Periode', 'KAPRODI • PERIODE', [
            'periode' => $periode,
            'search' => $search,
            'sort' => $sort,
            'direction' => $direction,
            'tahunAkademiks' => $tahunAkademiks,
            'sideCards' => [],
        ]));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'tahun_akademik_id' => ['required', 'exists:tahun_akademiks,id'],
            'semester' => ['required', 'in:1,2'],
            'sk_nomor' => ['nullable', 'string', 'max:255'],
            'sk_dokumen_url' => ['nullable', 'url', 'max:255'],
            'tgl_mulai' => ['required', 'date'],
            'tgl_selesai' => ['required', 'date', 'after:tgl_mulai'],
            
        ]);

        $tahunAkademik = TahunAkademik::findOrFail($validated['tahun_akademik_id']);
        $periodCode = $tahunAkademik->tahun_awal . $validated['semester'];
        $isActive = false;

        $data = array_merge($validated, [
            'kode_periode' => $periodCode,
            'is_aktif' => $isActive, 'status' => 'draft',
        ]);

        DB::transaction(function () use ($data, $isActive) {
            if ($isActive) {
                $this->closeOtherActivePeriods();
            }

            Periode::query()->create($data);
        });

        return redirect()
            ->route('kaprodi.periode.index')
            ->with('success', 'Periode berhasil ditambahkan.');
    }

    public function show(Request $request, Periode $periode): View|\Illuminate\Http\JsonResponse
    {
        $hasLinkedData = $this->hasLinkedData($periode);
        $assignedSearch = trim((string) $request->query('q', ''));
        $assignedSort = trim((string) $request->query('sort', ''));
        $assignedDirection = strtolower((string) $request->query('direction', 'desc')) === 'asc' ? 'asc' : 'desc';
        $assignedFase = trim((string) $request->query('fase', ''));

        $skripsis = Skripsi::query()
            ->where('periode_id', $periode->id)
            ->with(['student'])
            ->when($assignedSearch !== '', function ($query) use ($assignedSearch): void {
                $query->where(function ($innerQuery) use ($assignedSearch): void {
                    $innerQuery->where('title', 'like', "%{$assignedSearch}%")
                        ->orWhereHas('student', function ($studentQuery) use ($assignedSearch): void {
                            $studentQuery->where('name', 'like', "%{$assignedSearch}%")
                                ->orWhere('nim', 'like', "%{$assignedSearch}%");
                        });
                });
            })
            ->when($assignedFase !== '', function ($query) use ($assignedFase): void {
                $phaseMap = [
                    'proposal' => ['sidang_proposal', 'proposal'],
                    'bimbingan' => ['bimbingan_skripsi', 'bimbingan'],
                    'sidang_skripsi' => ['sidang_skripsi', 'revisi_sidang_skripsi'],
                    'review_dokumen_final' => ['review_dokumen_final'],
                ];

                $phases = $phaseMap[$assignedFase] ?? [$assignedFase];
                $query->whereIn('current_phase', $phases);
            })
            ->when(in_array($assignedSort, ['nim', 'mahasiswa'], true), function ($query) use ($assignedSort, $assignedDirection): void {
                $query->join('users', 'users.id', '=', 'skripsis.student_id')
                    ->orderBy($assignedSort === 'nim' ? 'users.nim' : 'users.name', $assignedDirection)
                    ->select('skripsis.*');
            })
            ->when($assignedSort === 'judul', fn ($query) => $query->orderBy('title', $assignedDirection))
            ->when($assignedSort === 'fase', fn ($query) => $query->orderBy('current_phase', $assignedDirection))
            ->when(! in_array($assignedSort, ['nim', 'mahasiswa', 'judul', 'fase'], true), fn ($query) => $query->orderByDesc('created_at'))
            ->paginate(10)
            ->withQueryString();

        if ($request->ajax() || $request->expectsJson()) {
            return response()->json([
                'table_html' => view('kaprodi.periode.partials.assigned-table', [
                    'periode' => $periode,
                    'assignedSkripsis' => $skripsis,
                    'assignedSort' => $assignedSort,
                    'assignedDirection' => $assignedDirection,
            'assignedFase' => $assignedFase,
                ])->render(),
                'pagination_html' => view('kaprodi.periode.partials.assigned-pagination', [
                    'assignedSkripsis' => $skripsis,
                ])->render(),
                'count_text' => $skripsis->total() . ' skripsi aktif terhubung.',
            ]);
        }

        return view('kaprodi.periode.show', $this->page('Detail Periode', 'KAPRODI • PERIODE', [
            'periode' => $periode,
            'hasLinkedData' => $hasLinkedData,
            'assignedSkripsis' => $skripsis,
            'assignedSearch' => $assignedSearch,
            'assignedSort' => $assignedSort,
            'assignedDirection' => $assignedDirection,
            'assignedFase' => $assignedFase,
            'identity' => [
                'avatar' => substr($periode->kode_periode, -2),
                'code' => $periode->kode_periode,
                'name' => $periode->name,
                'tahunAkademik' => $periode->tahunAkademik->name,
                'semester' => "Semester {$periode->semester}",
                'status' => ucfirst($periode->status),
            ],
        ]));
    }

    public function update(Request $request, Periode $periode): RedirectResponse
    {
        $validated = $request->validate([
            'tahun_akademik_id' => ['required', 'exists:tahun_akademiks,id'],
            'semester' => ['required', 'in:1,2'],
            'sk_nomor' => ['nullable', 'string', 'max:255'],
            'sk_dokumen_url' => ['nullable', 'url', 'max:255'],
            'tgl_mulai' => ['required', 'date'],
            'tgl_selesai' => ['required', 'date', 'after:tgl_mulai'],
            
        ]);

        $tahunAkademik = TahunAkademik::findOrFail($validated['tahun_akademik_id']);
        $periodCode = $tahunAkademik->tahun_awal . $validated['semester'];
        $isActive = false;

        $data = array_merge($validated, [
            'kode_periode' => $periodCode,
            'is_aktif' => $isActive, 'status' => 'draft',
        ]);

        DB::transaction(function () use ($periode, $data, $isActive) {
            if ($isActive) {
                $this->closeOtherActivePeriods($periode->id);
            }

            $periode->update($data);
        });

        return redirect()->back()->with('success', 'Data periode berhasil diperbarui.');
    }

    public function archive(Periode $periode): RedirectResponse
    {
        if (! $this->hasLinkedData($periode)) {
            return redirect()
                ->route('kaprodi.periode.show', $periode)
                ->with('error', 'Periode ini tidak memiliki data terkait. Gunakan hapus biasa.');
        }

        if ($periode->is_aktif || $periode->status === 'active') {
            $periode->update([
                'is_aktif' => false,
                'status' => 'closed',
            ]);
        }

        $periode->delete();

        return redirect()
            ->route('kaprodi.periode.index')
            ->with('success', 'Periode berhasil diarsipkan.');
    }

    public function destroy(Periode $periode): RedirectResponse
    {
        if ($this->hasLinkedData($periode)) {
            return redirect()
                ->route('kaprodi.periode.show', $periode)
                ->with('error', 'Periode dengan data terkait tidak bisa dihapus. Gunakan arsipkan.');
        }

        $periode->delete();

        return redirect()
            ->route('kaprodi.periode.index')
            ->with('success', 'Periode berhasil dihapus.');
    }

    private function hasLinkedData(Periode $periode): bool
    {
        return $periode->skripsis()->exists() || $periode->formats()->exists();
    }

    private function closeOtherActivePeriods(?int $exceptId = null): void
    {
        Periode::query()
            ->when($exceptId !== null, fn ($query) => $query->where('id', '!=', $exceptId))
            ->where(function ($query) {
                $query->where('is_aktif', true)
                    ->orWhere('status', 'active');
            })
            ->update([
                'is_aktif' => false,
                'status' => 'closed',
            ]);
    }

    /**
     * @param  array<string, mixed>  $extra
     * @return array<string, mixed>
     */
    private function page(string $heading, string $crumbs, array $extra = []): array
    {
        return $this->kaprodiPage($heading, $crumbs, $extra);
    }
}
