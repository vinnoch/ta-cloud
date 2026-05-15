<?php

namespace App\Http\Controllers\Kaprodi;

use App\Http\Controllers\Controller;
use App\Models\FormatPenilaian;
use App\Models\Grade;
use App\Models\Periode;
use App\Models\Skripsi;
use App\Models\StudyProgram;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class FormatPenilaianController extends Controller
{
    public function index(Request $request): View|\Illuminate\Http\JsonResponse
    {
        $search = $request->query('q');
        $jenis = $request->query('jenis', '');
        $sort = $request->query('sort', 'nama');
        $direction = $request->query('direction', 'asc');

        $formats = FormatPenilaian::query()
            ->with(['periodes', 'studyProgram', 'items'])
            ->when($search, function ($query) use ($search): void {
                $query->where('nama', 'like', "%{$search}%")
                    ->orWhereHas('periodes', fn ($q) => $q->where('kode_periode', 'like', "%{$search}%"));
            })
            ->when($jenis !== '', fn ($query) => $query->where('template_type', $jenis))
            ->when($sort === 'nama', fn ($q) => $q->orderBy('nama', $direction))
            ->when($sort === 'format_type', fn ($q) => $q->orderBy('template_type', $direction))
            ->when($sort === 'periode', fn ($q) => $q->withAggregate('periodes as periode_sort', 'kode_periode')->orderBy('periode_sort', $direction))
            ->when(! in_array($sort, ['nama', 'format_type', 'periode'], true), fn ($q) => $q->orderByDesc('created_at'))
            ->paginate(10)
            ->withQueryString();

        if ($request->ajax() || $request->expectsJson()) {
            return response()->json([
                'table_html' => view('kaprodi.format-penilaian.partials.table', [
                    'formats' => $formats,
                    'search' => $search,
                    'jenis' => $jenis,
                    'sort' => $sort,
                    'direction' => $direction,
                ])->render(),
                'pagination_html' => view('kaprodi.format-penilaian.partials.pagination', [
                    'formats' => $formats,
                ])->render(),
                'count_text' => $formats->total() . ' format ditemukan.',
            ]);
        }

        return view('kaprodi.format-penilaian.index', $this->page('Format Nilai', 'MASTER DATA • KAPRODI', [
            'formats' => $formats,
            'search' => $search,
            'jenis' => $jenis,
            'sort' => $sort,
            'direction' => $direction,
        ]));
    }

    public function create(): View
    {
        $periodes = $this->availablePeriodes();
        $activePeriodeId = $periodes
            ->first(fn ($periode) => (bool) $periode->is_aktif || $periode->status === 'active')?->id
            ?? $periodes->first()?->id;

        return view('kaprodi.format-penilaian.create', $this->page('Tambah Format Nilai', 'KAPRODI • FORMAT NILAI', [
            'periodes' => $periodes,
            'studyProgram' => Auth::user()->studyProgram,
            'activePeriodeId' => $activePeriodeId,
            'format' => new \App\Models\FormatPenilaian(),
        ]));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validateFormat($request);

        if ($response = $this->validateWeightTotal($validated['items'])) {
            return $response;
        }

        DB::transaction(function () use ($validated): void {
            $format = FormatPenilaian::query()->create([
                'study_program_id' => Auth::user()->study_program_id,
                'nama' => $validated['name'],
                'template_type' => $validated['format_type'],
                'is_published' => $validated['status'] === 'published',
                'is_locked' => false,
            ]);

            $format->periodes()->sync([$validated['periode_id']]);

            foreach ($validated['items'] as $index => $item) {
                $format->items()->create([
                    'nama' => $item['name'],
                    'kode' => $item['code'],
                    'bobot' => $item['weight'],
                    'sort_order' => $index + 1,
                ]);
            }
        });

        return redirect()->route('kaprodi.formats.index')
            ->with('success', 'Format penilaian berhasil dibuat.');
    }

    public function show(FormatPenilaian $format, Request $request): View
    {
        $assignedSearch = $request->query('q');
        $assignedSort = $request->query('sort', 'mahasiswa');
        $assignedDirection = $request->query('direction', 'asc');

        $assignedSkripsis = Skripsi::query()
            ->whereIn('skripsis.id', Grade::query()
                ->select('skripsi_id')
                ->where('format_penilaian_id', $format->id)
                ->distinct())
            ->with(['student', 'periode'])
            ->when($assignedSearch, function ($query) use ($assignedSearch): void {
                $query->where(function ($inner) use ($assignedSearch): void {
                    $inner->whereHas('student', function ($q) use ($assignedSearch): void {
                        $q->where('name', 'like', "%{$assignedSearch}%")
                            ->orWhere('nim', 'like', "%{$assignedSearch}%");
                    })->orWhere('title', 'like', "%{$assignedSearch}%");
                });
            })
            ->when($assignedSort === 'nim', fn ($q) => $q->join('users as students_sort', 'students_sort.id', '=', 'skripsis.student_id')->orderBy('students_sort.nim', $assignedDirection)->select('skripsis.*'))
            ->when($assignedSort === 'mahasiswa', fn ($q) => $q->join('users as students_sort', 'students_sort.id', '=', 'skripsis.student_id')->orderBy('students_sort.name', $assignedDirection)->select('skripsis.*'))
            ->when($assignedSort === 'judul', fn ($q) => $q->orderBy('title', $assignedDirection))
            ->paginate(10)
            ->withQueryString();

        if ($request->ajax()) {
            return view('kaprodi.format-penilaian.partials.assigned-table', [
                'assignedSkripsis' => $assignedSkripsis,
                'format' => $format,
                'assignedSearch' => $assignedSearch,
                'assignedSort' => $assignedSort,
                'assignedDirection' => $assignedDirection,
            ]);
        }

        
        $identity = [
            'name' => $format->nama,
            'avatar' => mb_substr($format->nama, 0, 1),
        ];

        return view('kaprodi.format-penilaian.show', $this->page('Rincian Format Nilai', 'KAPRODI • FORMAT NILAI', [
            'identity' => $identity,
            'format' => $format->load(['items', 'periodes']),
            'assignedSkripsis' => $assignedSkripsis,
            'assignedSearch' => $assignedSearch,
            'assignedSort' => $assignedSort,
            'assignedDirection' => $assignedDirection,
        ]));
    }

    public function edit(FormatPenilaian $format): View
    {
        if ($format->is_locked) {
            return back()->with('error', 'Format penilaian yang sudah terkunci tidak dapat diubah.');
        }

        return view('kaprodi.format-penilaian.edit', $this->page('Ubah Format Nilai', 'KAPRODI • FORMAT NILAI', [
            'format' => $format->load('items'),
            'periodes' => $this->availablePeriodes($format),
            'prodi' => Auth::user()->studyProgram,
        ]));
    }

    public function update(Request $request, FormatPenilaian $format): RedirectResponse
    {
        if ($format->is_locked) {
            return back()->with('error', 'Format penilaian yang sudah terkunci tidak dapat diubah.');
        }

        $validated = $this->validateFormat($request, true);

        if ($response = $this->validateWeightTotal($validated['items'])) {
            return $response;
        }

        DB::transaction(function () use ($format, $validated): void {
            $format->update([
                'nama' => $validated['name'],
                'template_type' => $validated['format_type'],
                'is_published' => $validated['status'] === 'published',
            ]);

            $format->periodes()->sync([$validated['periode_id']]);

            $this->syncItems($format, $validated['items']);
        });

        return redirect()->route('kaprodi.formats.index')
            ->with('success', 'Format penilaian berhasil diperbarui.');
    }

    public function destroy(FormatPenilaian $format): RedirectResponse
    {
        if ($format->is_locked) {
            return back()->with('error', 'Format penilaian yang sudah terkunci tidak dapat dihapus.');
        }

        $format->delete();

        return redirect()->route('kaprodi.formats.index')
            ->with('success', 'Format penilaian berhasil dihapus.');
    }

    public function duplicate(FormatPenilaian $format): RedirectResponse
    {
        DB::transaction(function () use ($format): void {
            $newFormat = $format->replicate();
            $newFormat->nama = $this->duplicateName($format->nama);
            $newFormat->is_published = false;
            $newFormat->is_locked = false;
            $newFormat->save();

            $newFormat->periodes()->sync($format->periodes->pluck('id')->all());

            foreach ($format->items as $item) {
                $newItem = $item->replicate();
                $newItem->format_penilaian_id = $newFormat->id;
                $newItem->save();
            }
        });

        return redirect()->route('kaprodi.formats.index')
            ->with('success', 'Format penilaian berhasil diduplikasi.');
    }

    public function showGrades(FormatPenilaian $format, Skripsi $skripsi): View
    {
        return $this->grades($format, $skripsi);
    }

    public function grades(FormatPenilaian $format, Skripsi $skripsi): View
    {
        $grades = Grade::query()
            ->where('format_penilaian_id', $format->id)
            ->where('skripsi_id', $skripsi->id)
            ->with('reviewer')
            ->latest()
            ->get();

        $averageFinalScore = $grades->whereNotNull('score')->avg('score');

        return view('kaprodi.format-penilaian.grades', $this->page('Nilai Mahasiswa', 'KAPRODI • FORMAT NILAI', [
            'format' => $format,
            'skripsi' => $skripsi,
            'grades' => $grades,
            'averageFinalScore' => $averageFinalScore,
            'sideCards' => [],
        ]));
    }

    private function validateFormat(Request $request, bool $isUpdate = false): array
    {
        $rules = [
            'name' => ['nullable', 'string', 'max:255'],
            'nama' => ['nullable', 'string', 'max:255'],
            'format_type' => ['required', 'in:sidang_proposal,sidang_skripsi'],
            'status' => ['required', 'in:draft,published'],
            'periode_id' => ['required', 'exists:periodes,id'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.name' => ['required', 'string'],
            'items.*.code' => ['required', 'string'],
            'items.*.weight' => ['required', 'integer', 'min:1', 'max:100'],
        ];

        if ($isUpdate) {
            $rules['items.*.id'] = ['nullable', 'exists:item_penilaians,id'];
        }

        $validated = $request->validate($rules);
        $validated['name'] = $validated['name'] ?? $validated['nama'] ?? null;

        $normalizedCodes = [];
        foreach ($validated['items'] as $index => $item) {
            $generatedCode = Str::of($item['name'] ?? '')
                ->lower()
                ->trim()
                ->replaceMatches('/\s+/', '_')
                ->replaceMatches('/[^a-z0-9_]/', '')
                ->toString();

            $generatedCode = $generatedCode !== '' ? $generatedCode : 'item_penilaian_' . ($index + 1);

            $validated['items'][$index]['code'] = $generatedCode;
            $normalizedCodes[] = $generatedCode;
        }

        if (count($normalizedCodes) !== count(array_unique($normalizedCodes))) {
            throw ValidationException::withMessages([
                'items' => 'Nama item penilaian tidak boleh sama dalam satu format nilai.',
            ]);
        }

        return $validated;
    }

    private function validateWeightTotal(array $items): ?RedirectResponse
    {
        $totalWeight = collect($items)->sum(fn (array $item) => (int) ($item['weight'] ?? 0));

        if ($totalWeight === 100) {
            return null;
        }

        return back()->withErrors(['items' => 'Total bobot item harus berjumlah 100%.'])->withInput();
    }

    private function syncItems(FormatPenilaian $format, array $items): void
    {
        $existingItemIds = collect($items)->pluck('id')->filter()->map(fn ($id) => (int) $id)->all();

        if ($existingItemIds === []) {
            $format->items()->delete();
        } else {
            $format->items()->whereNotIn('id', $existingItemIds)->delete();
        }

        foreach (array_values($items) as $index => $itemData) {
            $format->items()->updateOrCreate(
                ['id' => $itemData['id'] ?? null],
                [
                    'nama' => $itemData['name'],
                    'kode' => $itemData['code'],
                    'bobot' => $itemData['weight'],
                    'sort_order' => $index + 1,
                ]
            );
        }
    }

    private function availablePeriodes(?FormatPenilaian $format = null)
    {
        return Periode::query()
            ->with('tahunAkademik')
            ->where(function ($query) use ($format): void {
                $query->whereDoesntHave('formats');

                if ($format !== null) {
                    $query->orWhereHas('formats', function ($innerQuery) use ($format): void {
                        $innerQuery->where('format_penilaians.id', $format->id);
                    });
                }
            })
            ->orderByDesc('kode_periode')
            ->get();
    }

    private function duplicateName(string $name): string
    {
        $baseName = trim($name) !== '' ? trim($name) : 'Format Baru';
        $candidate = $baseName . ' (Copy)';
        $counter = 2;

        while (FormatPenilaian::query()->where('nama', $candidate)->exists()) {
            $candidate = $baseName . ' (Copy ' . $counter . ')';
            $counter++;
        }

        return $candidate;
    }

    private function page(string $title, string $subtitle, array $data = []): array
    {
        $navigation = app(\App\Services\RoleNavigationService::class);
        
        return array_merge([
            'title' => $title,
            'heading' => $title,
            'crumbs' => $subtitle,
            'navItems' => $navigation->kaprodiNavItems(),
            'navFooterItems' => $navigation->footerItems(),
            'navRole' => 'kaprodi',
            'primaryCta' => null,
        ], $data);
    }
}
