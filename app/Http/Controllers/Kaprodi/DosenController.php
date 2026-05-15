<?php

namespace App\Http\Controllers\Kaprodi;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Skripsi;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class DosenController extends Controller
{
    use BuildsKaprodiPage;
    public function index(Request $request): View|\Illuminate\Http\JsonResponse
    {
        $search = $request->string('q')->toString();
        $sort = $request->string('sort')->toString();
        $direction = strtolower($request->string('direction')->toString()) === 'desc' ? 'desc' : 'asc';
        $sort = in_array($sort, ['name', 'email', 'nidn_nip'], true) ? $sort : 'name';
        $status = $request->string('status')->toString() ?: 'active';

        $dosen = User::query()
            ->when($status === 'archived', fn ($query) => $query->onlyTrashed())
            ->when($status === 'all', fn ($query) => $query->withTrashed())
            ->forRole('dosen')
            ->withExists(['reviewerAssignments as has_related_records', 'reviewedBimbingans as has_reviewed_bimbingans', 'reviewedGrades as has_reviewed_grades'])
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('nidn_nip', 'like', "%{$search}%");
                });
            })
            ->orderBy($sort, $direction)
            ->paginate(10)
            ->withQueryString();

        if ($request->ajax() || $request->expectsJson()) {
            return response()->json([
                'table_html' => view('kaprodi.dosen.partials.table', ['dosen' => $dosen, 'sort' => $sort, 'direction' => $direction])->render(),
                'pagination_html' => view('kaprodi.dosen.partials.pagination', ['dosen' => $dosen])->render(),
                'count_text' => $dosen->total() . ' akun dosen ditemukan.',
            ]);
        }

                $archivedCount = User::onlyTrashed()->forRole('dosen')->count();

        return view('kaprodi.dosen.index', $this->page('Master Dosen', 'KAPRODI • DOSEN', [
            'dosen' => $dosen,
            'search' => $search,
            'sort' => $sort,
            'direction' => $direction,
            'status' => $status,
            'archivedCount' => $archivedCount,
            'sideCards' => [],
        ]));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'nidn_nip' => ['nullable', 'string', 'max:255', 'unique:users,nidn_nip'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        User::query()->create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'nidn_nip' => $validated['nidn_nip'] ?? null,
            'password' => $validated['password'],
            'role' => 'dosen',
        ]);

        return redirect()
            ->route('kaprodi.dosen.index')
            ->with('success', 'Dosen berhasil ditambahkan.');
    }

    public function show(Request $request, User $dosen): View
    {
        $this->ensureDosen($dosen);

        $search = trim((string) $request->query('q', ''));
        $statusFilter = trim((string) $request->query('status', ''));

        $mahasiswaBimbingan = $dosen->reviewerAssignments()
            ->whereIn('role_type', ['pembimbing_1', 'pembimbing_2'])
            ->with([
                'skripsi.student',
                'skripsi.bimbingans' => fn ($query) => $query
                    ->where('reviewer_id', $dosen->id)
                    ->latest('meeting_date'),
            ])
            ->get()
            ->map(function ($assignment) use ($dosen): array {
                $skripsi = $assignment->skripsi;
                $student = $skripsi?->student;
                $latestBimbingan = $skripsi?->bimbingans?->first();

                return [
                    'student' => $student,
                    'skripsi' => $skripsi,
                    'skripsi_url' => $skripsi ? route('kaprodi.skripsi.show', $skripsi) : null,
                    'bimbingan_url' => $skripsi ? route('kaprodi.skripsi.bimbingan', $skripsi) : null,
                    'skripsi_topic' => $skripsi?->title ?? '-',
                    'guidance_count' => $skripsi?->bimbingans?->count() ?? 0,
                    'last_guidance_title' => $latestBimbingan?->lecturer_notes ?? '-',
                    'status_key' => $skripsi?->current_phase ?? '',
                    'status' => $skripsi
                        ? str($skripsi->current_phase)
                            ->replace(['bimbingan_skripsi', 'Bimbingan Skripsi'], 'Bimb.Skripsi')
                            ->replace(['_', '-'], ' ')
                            ->title()
                            ->replace('Bimb.skripsi', 'Bimb.Skripsi')
                            ->toString()
                        : '-',
                ];
            })
            ->filter(fn (array $item): bool => ! empty($item['student']) && ! empty($item['skripsi']))
            ->values();

        if ($mahasiswaBimbingan->isEmpty()) {
            $mahasiswaBimbingan = User::query()
                ->forRole('mahasiswa')
                ->orderBy('name')
                ->limit(6)
                ->get()
                ->map(function (User $mahasiswa): array {
                    return [
                        'student' => $mahasiswa,
                        'skripsi' => null,
                        'skripsi_url' => null,
                        'bimbingan_url' => null,
                        'skripsi_topic' => '-',
                        'guidance_count' => 0,
                        'last_guidance_title' => '-',
                        'status' => 'Aktif',
                    ];
                });
        }

        $mahasiswaBimbingan = $mahasiswaBimbingan
            ->when($search !== '', function ($collection) use ($search) {
                $needle = mb_strtolower($search);

                return $collection->filter(function (array $item) use ($needle): bool {
                    $studentName = mb_strtolower((string) ($item['student']?->name ?? ''));
                    $studentNim = mb_strtolower((string) ($item['student']?->nim ?? ''));
                    $title = mb_strtolower((string) ($item['skripsi_topic'] ?? ''));

                    return str_contains($studentName, $needle)
                        || str_contains($studentNim, $needle)
                        || str_contains($title, $needle);
                })->values();
            })
            ->when($statusFilter !== '', fn ($collection) => $collection->filter(fn (array $item): bool => ($item['status_key'] ?? '') === $statusFilter)->values());

        $hasRelatedRecords = $this->hasRelatedRecords($dosen);

        return view('kaprodi.dosen.show', $this->page('Detail Dosen', 'KAPRODI • DOSEN', [
            'dosen' => $dosen,
            'hasRelatedRecords' => $hasRelatedRecords,
            'identity' => [
                'avatar' => collect(explode(' ', $dosen->name))->map(fn(string $part): string => mb_substr($part, 0, 1))->take(2)->implode(''),
                'name' => $dosen->name,
                'nidn' => $dosen->nidn_nip ? 'NIDN/NIP: ' . $dosen->nidn_nip : 'NIDN/NIP: -',
                'program' => 'Sistem Informasi',
            ],
            'search' => $search,
            'statusFilter' => $statusFilter,
            'mahasiswaBimbingan' => $mahasiswaBimbingan,
        ]));
    }

    public function update(Request $request, User $dosen): RedirectResponse
    {
        $this->ensureDosen($dosen);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($dosen->id)],
            'nidn_nip' => ['nullable', 'string', 'max:255', Rule::unique('users', 'nidn_nip')->ignore($dosen->id)],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
        ]);

        $dosen->fill([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'nidn_nip' => $validated['nidn_nip'] ?? null,
        ]);

        if (! empty($validated['password'])) {
            $dosen->password = Hash::make($validated['password']);
        }

        $dosen->role = 'dosen';
        $dosen->save();

        return redirect()
            ->route('kaprodi.dosen.show', $dosen)
            ->with('success', 'Data dosen berhasil diperbarui.');
    }

    public function archive(User $dosen): RedirectResponse
    {
        $this->ensureDosen($dosen);

        if (! $this->hasRelatedRecords($dosen)) {
            return redirect()
                ->route('kaprodi.dosen.show', $dosen)
                ->with('error', 'Dosen ini tidak punya data terkait. Gunakan hapus biasa.');
        }

        $dosen->delete();

        return redirect()
            ->route('kaprodi.dosen.index', ['status' => 'archived'])
            ->with('success', 'Dosen berhasil diarsipkan.');
    }

    public function restore(int $id): RedirectResponse
    {
        $dosen = User::withTrashed()->findOrFail($id);
        $this->ensureDosen($dosen);

        if (! $dosen->trashed()) {
            return redirect()
                ->route('kaprodi.dosen.index')
                ->with('error', 'Dosen ini tidak sedang diarsipkan.');
        }

        $dosen->restore();

        return redirect()
            ->route('kaprodi.dosen.index')
            ->with('success', 'Dosen berhasil dipulihkan.');
    }

    public function destroy(int $id): RedirectResponse
    {
        $dosen = User::withTrashed()->findOrFail($id);
        $this->ensureDosen($dosen);

        if ($this->hasRelatedRecords($dosen)) {
            return redirect()
                ->route('kaprodi.dosen.index', ['status' => $dosen->trashed() ? 'archived' : 'active'])
                ->with('error', 'Dosen dengan data terkait tidak bisa dihapus permanen. Pulihkan data jika diperlukan.');
        }

        $dosen->forceDelete();

        return redirect()
            ->route('kaprodi.dosen.index')
            ->with('success', 'Dosen berhasil dihapus permanen.');
    }

    /**
     * @param  array<string, mixed>  $extra
     * @return array<string, mixed>
     */
    private function page(string $heading, string $crumbs, array $extra = []): array
    {
        return $this->kaprodiPage($heading, $crumbs, $extra);
    }

    private function hasRelatedRecords(User $dosen): bool
    {
        return $dosen->reviewerAssignments()->exists()
            || $dosen->reviewedBimbingans()->exists()
            || $dosen->reviewedGrades()->exists();
    }

    private function ensureDosen(User $dosen): void
    {
        abort_unless($dosen->role === 'dosen', 404);
    }
}
