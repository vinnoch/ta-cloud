<?php

namespace App\Http\Controllers\Kaprodi;

use App\Http\Controllers\Controller;
use App\Models\DocumentTemplate;
use App\Models\Periode;
use App\Models\Skripsi;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class DocumentTemplateController extends Controller
{
    use BuildsKaprodiPage;

    public function index(Request $request): View|JsonResponse
    {
        $search = trim((string) $request->query('q', ''));
        $sort = (string) $request->query('sort', 'nama');
        $direction = strtolower((string) $request->query('direction', 'asc')) === 'desc' ? 'desc' : 'asc';

        $templates = DocumentTemplate::query()
            ->with(['periodes.tahunAkademik', 'items'])
            ->when($search !== '', function ($query) use ($search): void {
                $query->where('nama', 'like', "%{$search}%")
                    ->orWhereHas('periodes', fn ($periodeQuery) => $periodeQuery->where('kode_periode', 'like', "%{$search}%"));
            })
            ->when($sort === 'nama', fn ($query) => $query->orderBy('nama', $direction))
            ->when($sort === 'periode', fn ($query) => $query->withAggregate('periodes as periode_sort', 'kode_periode')->orderBy('periode_sort', $direction))
            ->when($sort === 'item', fn ($query) => $query->withCount('items')->orderBy('items_count', $direction))
            ->when(! in_array($sort, ['nama', 'periode', 'item'], true), fn ($query) => $query->latest())
            ->paginate(10)
            ->withQueryString();

        $templates->getCollection()->transform(function (DocumentTemplate $template): DocumentTemplate {
            $template->is_locked = $this->isTemplateLocked($template);
            return $template;
        });

        if ($request->ajax() || $request->expectsJson()) {
            return response()->json([
                'table_html' => view('kaprodi.document-templates.partials.table', [
                    'templates' => $templates,
                    'sort' => $sort,
                    'direction' => $direction,
                ])->render(),
                'pagination_html' => view('kaprodi.document-templates.partials.pagination', [
                    'templates' => $templates,
                ])->render(),
                'count_text' => $templates->total() . ' template ditemukan.',
            ]);
        }

        return view('kaprodi.document-templates.index', $this->kaprodiPage('Dokumen Final', 'MASTER DATA • KAPRODI', [
            'templates' => $templates,
            'search' => $search,
            'sort' => $sort,
            'direction' => $direction,
        ]));
    }

    public function create(): View
    {
        $periodes = $this->availablePeriodes();
        $activePeriodeId = $periodes->first(fn ($periode) => (bool) $periode->is_aktif || $periode->status === 'active')?->id;

        return view('kaprodi.document-templates.create', $this->kaprodiPage('Tambah Dokumen Final', 'KAPRODI • DOKUMEN FINAL', [
            'template' => new DocumentTemplate(),
            'periodes' => $periodes,
            'activePeriodeId' => $activePeriodeId,
        ]));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validateTemplate($request);

        DB::transaction(function () use ($validated): void {
            $template = DocumentTemplate::query()->create([
                'study_program_id' => Auth::user()->study_program_id,
                'nama' => $validated['name'],
                'is_published' => $validated['status'] === 'published',
                'is_locked' => false,
            ]);

            $template->periodes()->sync($validated['periode_ids']);

            foreach ($validated['items'] as $index => $item) {
                $template->items()->create([
                    'nama' => $item['name'],
                    'kode' => $item['code'],
                    'type' => $item['type'],
                    'is_required' => (bool) ($item['is_required'] ?? true),
                    'sort_order' => $index + 1,
                ]);
            }
        });

        return redirect()->route('kaprodi.document-templates.index')
            ->with('success', 'Template dokumen final berhasil dibuat.');
    }

    public function show(DocumentTemplate $documentTemplate, Request $request): View|JsonResponse
    {
        $documentTemplate->loadMissing(['items', 'periodes.tahunAkademik']);
        $documentTemplate->is_locked = $this->isTemplateLocked($documentTemplate);

        $assignedSearch = trim((string) $request->query('assigned_q', ''));
        $assignedPeriodeId = (int) $request->query('assigned_periode_id', 0);
        $assignedSort = (string) $request->query('assigned_sort', 'mahasiswa');
        $assignedDirection = strtolower((string) $request->query('assigned_direction', 'asc')) === 'desc' ? 'desc' : 'asc';

        $assignedSkripsis = Skripsi::query()
            ->with(['student', 'periode.tahunAkademik'])
            ->whereIn('skripsis.id', \App\Models\DocumentSubmission::query()
                ->whereIn('document_template_item_id', $documentTemplate->items->pluck('id'))
                ->select('skripsi_id'))
            ->when($assignedPeriodeId > 0, fn ($query) => $query->where('periode_id', $assignedPeriodeId))
            ->when($assignedSearch !== '', function ($query) use ($assignedSearch): void {
                $query->where(function ($inner) use ($assignedSearch): void {
                    $inner->where('title', 'like', "%{$assignedSearch}%")
                        ->orWhereHas('student', fn ($studentQuery) => $studentQuery
                            ->where('name', 'like', "%{$assignedSearch}%")
                            ->orWhere('nim', 'like', "%{$assignedSearch}%"));
                });
            })
            ->when($assignedSort === 'mahasiswa', fn ($query) => $query
                ->join('users as student_sort', 'student_sort.id', '=', 'skripsis.student_id')
                ->select('skripsis.*')
                ->orderBy('student_sort.name', $assignedDirection))
            ->when($assignedSort === 'judul', fn ($query) => $query->orderBy('title', $assignedDirection))
            ->when($assignedSort === 'periode', fn ($query) => $query
                ->join('periodes as periode_sort', 'periode_sort.id', '=', 'skripsis.periode_id')
                ->select('skripsis.*')
                ->orderBy('periode_sort.kode_periode', $assignedDirection))
            ->when(! in_array($assignedSort, ['mahasiswa', 'judul', 'periode'], true), fn ($query) => $query->latest('id'))
            ->paginate(10)
            ->withQueryString();

        if ($request->ajax() || $request->expectsJson()) {
            return response()->json([
                'table_html' => view('kaprodi.document-templates.partials.assigned-table', [
                    'template' => $documentTemplate,
                    'assignedSkripsis' => $assignedSkripsis,
                    'assignedSort' => $assignedSort,
                    'assignedDirection' => $assignedDirection,
                ])->render(),
                'pagination_html' => view('kaprodi.document-templates.partials.assigned-pagination', [
                    'assignedSkripsis' => $assignedSkripsis,
                ])->render(),
                'count_text' => $assignedSkripsis->total() . ' skripsi terhubung.',
            ]);
        }

        $periodeIdsWithSubmissions = \App\Models\DocumentSubmission::query()
            ->join('skripsis', 'skripsis.id', '=', 'document_submissions.skripsi_id')
            ->whereIn('document_submissions.document_template_item_id', $documentTemplate->items->pluck('id'))
            ->distinct()
            ->pluck('skripsis.periode_id')
            ->toArray();

        return view('kaprodi.document-templates.show', $this->kaprodiPage('Detail Dokumen Final', 'KAPRODI • DOKUMEN FINAL', [
            'template' => $documentTemplate,
            'assignedSkripsis' => $assignedSkripsis,
            'assignedSearch' => $assignedSearch,
            'assignedPeriodeId' => $assignedPeriodeId,
            'assignedSort' => $assignedSort,
            'assignedDirection' => $assignedDirection,
            'availableAddPeriodes' => $this->availablePeriodes($documentTemplate),
            'periodeIdsWithSubmissions' => $periodeIdsWithSubmissions,
        ]));
    }

    public function edit(DocumentTemplate $documentTemplate): View
    {
        if ($this->isTemplateLocked($documentTemplate)) {
            return redirect()->route('kaprodi.document-templates.show', $documentTemplate)
                ->with('error', 'Template dokumen final terkunci. Anda hanya bisa menambah periode dari halaman detail.');
        }

        $documentTemplate->loadMissing(['items', 'periodes']);

        return view('kaprodi.document-templates.edit', $this->kaprodiPage('Edit Dokumen Final', 'KAPRODI • DOKUMEN FINAL', [
            'template' => $documentTemplate,
            'periodes' => $this->availablePeriodes(),
            'activePeriodeId' => $documentTemplate->periodes->first()?->id,
        ]));
    }

    public function update(Request $request, DocumentTemplate $documentTemplate): RedirectResponse
    {
        if ($this->isTemplateLocked($documentTemplate)) {
            return redirect()->route('kaprodi.document-templates.show', $documentTemplate)
                ->with('error', 'Template dokumen final terkunci. Edit struktur tidak diizinkan.');
        }

        $validated = $this->validateTemplate($request, true);

        DB::transaction(function () use ($documentTemplate, $validated): void {
            $documentTemplate->update([
                'nama' => $validated['name'],
                'is_published' => $validated['status'] === 'published',
            ]);

            $documentTemplate->periodes()->sync($validated['periode_ids']);
            $this->syncItems($documentTemplate, $validated['items']);
        });

        return redirect()->route('kaprodi.document-templates.show', $documentTemplate)
            ->with('success', 'Template dokumen final berhasil diperbarui.');
    }

    public function destroy(DocumentTemplate $documentTemplate): RedirectResponse
    {
        if ($this->isTemplateLocked($documentTemplate)) {
            return back()->with('error', 'Template dokumen final yang sudah digunakan tidak dapat dihapus.');
        }

        $documentTemplate->delete();

        return redirect()->route('kaprodi.document-templates.index')
            ->with('success', 'Template dokumen final berhasil dihapus.');
    }

    public function duplicate(DocumentTemplate $documentTemplate): RedirectResponse
    {
        $documentTemplate->loadMissing(['items', 'periodes']);

        DB::transaction(function () use ($documentTemplate): void {
            $newTemplate = $documentTemplate->replicate();
            $newTemplate->nama = $this->duplicateName($documentTemplate->nama);
            $newTemplate->is_published = false;
            $newTemplate->is_locked = false;
            $newTemplate->save();

            $newTemplate->periodes()->sync($documentTemplate->periodes->pluck('id')->all());

            foreach ($documentTemplate->items as $item) {
                $newItem = $item->replicate();
                $newItem->document_template_id = $newTemplate->id;
                $newItem->save();
            }
        });

        return redirect()->route('kaprodi.document-templates.index')
            ->with('success', 'Template dokumen final berhasil diduplikasi.');
    }

    public function addPeriode(Request $request, DocumentTemplate $documentTemplate): RedirectResponse
    {
        if (! $this->isTemplateLocked($documentTemplate)) {
            return back()->with('error', 'Tambah periode khusus untuk template dokumen final yang sudah terkunci.');
        }

        $validated = $request->validate([
            'periode_id' => ['required', 'exists:periodes,id'],
        ]);

        if ($documentTemplate->periodes()->where('periodes.id', $validated['periode_id'])->exists()) {
            return back()->with('error', 'Periode ini sudah terhubung dengan template dokumen final.');
        }

        $documentTemplate->periodes()->attach($validated['periode_id']);

        return back()->with('success', 'Periode berhasil ditambahkan ke template dokumen final ini.');
    }

    public function removePeriode(DocumentTemplate $documentTemplate, Periode $periode): RedirectResponse
    {
        if (! $this->isTemplateLocked($documentTemplate)) {
            return back()->with('error', 'Aksi ini hanya untuk template dokumen final yang sudah terkunci.');
        }

        // Check if there is any student document submitted using this template under this period
        $hasStudentData = $documentTemplate->items()->whereHas('studentDocuments', function ($query) use ($periode) {
            $query->whereHas('skripsi', function ($q) use ($periode) {
                $q->where('periode_id', $periode->id);
            });
        })->exists();

        if ($hasStudentData) {
            return back()->with('error', 'Periode ini sudah digunakan untuk pengumpulan dokumen final mahasiswa.');
        }

        $documentTemplate->periodes()->detach($periode->id);

        return back()->with('success', 'Periode berhasil dilepas dari template dokumen final ini.');
    }

    private function validateTemplate(Request $request, bool $isUpdate = false): array
    {
        $rules = [
            'name' => ['nullable', 'string', 'max:255'],
            'nama' => ['nullable', 'string', 'max:255'],
            'status' => ['required', 'in:draft,published'],
            'periode_ids' => ['required', 'array', 'min:1'],
            'periode_ids.*' => ['required', 'exists:periodes,id'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.name' => ['required', 'string', 'max:255'],
            'items.*.type' => ['required', 'in:file,link'],
            'items.*.is_required' => ['nullable', 'boolean'],
        ];

        if ($isUpdate) {
            $rules['items.*.id'] = ['nullable', 'exists:document_template_items,id'];
        }

        $validated = $request->validate($rules);
        $validated['name'] = $validated['name'] ?? $validated['nama'] ?? null;

        $normalizedCodes = [];
        foreach ($validated['items'] as $index => $item) {
            $generatedCode = Str::of((string) ($item['name'] ?? ''))
                ->lower()
                ->trim()
                ->replaceMatches('/\s+/', '_')
                ->replaceMatches('/[^a-z0-9_]/', '')
                ->toString();

            $generatedCode = $generatedCode !== '' ? $generatedCode : 'dokumen_final_' . ($index + 1);

            $validated['items'][$index]['code'] = $generatedCode;
            $validated['items'][$index]['type'] = $item['type'] ?? 'file';
            $validated['items'][$index]['is_required'] = (bool) ($item['is_required'] ?? false);
            $normalizedCodes[] = $generatedCode;
        }

        if (count($normalizedCodes) !== count(array_unique($normalizedCodes))) {
            throw ValidationException::withMessages([
                'items' => 'Nama item dokumen tidak boleh sama dalam satu template.',
            ]);
        }

        return $validated;
    }

    private function syncItems(DocumentTemplate $documentTemplate, array $items): void
    {
        $existingItemIds = collect($items)->pluck('id')->filter()->map(fn ($id) => (int) $id)->all();

        if ($existingItemIds === []) {
            $documentTemplate->items()->delete();
        } else {
            $documentTemplate->items()->whereNotIn('id', $existingItemIds)->delete();
        }

        foreach (array_values($items) as $index => $itemData) {
            $documentTemplate->items()->updateOrCreate(
                ['id' => $itemData['id'] ?? null],
                [
                    'nama' => $itemData['name'],
                    'kode' => $itemData['code'],
                    'type' => $itemData['type'] ?? 'file',
                    'is_required' => (bool) ($itemData['is_required'] ?? false),
                    'sort_order' => $index + 1,
                ]
            );
        }
    }

    private function availablePeriodes(?DocumentTemplate $documentTemplate = null)
    {
        $selectedIds = $documentTemplate?->periodes?->pluck('id')->all() ?? [];

        return Periode::query()
            ->with('tahunAkademik')
            ->withCount('documentTemplates')
            ->orderByDesc('kode_periode')
            ->get()
            ->reject(fn (Periode $periode) => $documentTemplate && in_array($periode->id, $selectedIds, true))
            ->values();
    }

    private function duplicateName(string $name): string
    {
        $baseName = trim($name) !== '' ? trim($name) : 'Template Baru';
        $candidate = $baseName . ' (Copy)';
        $counter = 2;

        while (DocumentTemplate::query()->where('nama', $candidate)->exists()) {
            $candidate = $baseName . ' (Copy ' . $counter . ')';
            $counter++;
        }

        return $candidate;
    }

    private function isTemplateLocked(DocumentTemplate $documentTemplate): bool
    {
        if ($documentTemplate->is_locked) {
            return true;
        }

        return Skripsi::query()
            ->whereIn('periode_id', $documentTemplate->periodes()->pluck('periodes.id'))
            ->whereHas('documentVersions', fn ($query) => $query->where('phase', 'skripsi_final'))
            ->exists();
    }
}
