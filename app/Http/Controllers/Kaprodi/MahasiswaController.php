<?php

namespace App\Http\Controllers\Kaprodi;

use App\Http\Controllers\Controller;
use App\Models\Skripsi;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class MahasiswaController extends Controller
{
    use BuildsKaprodiPage;
    public function index(Request $request): View|\Illuminate\Http\JsonResponse
    {
        $search = $request->string('q')->toString();
        $sort = $request->string('sort')->toString();
        $direction = strtolower($request->string('direction')->toString()) === 'asc' ? 'asc' : 'desc';
        $status = $request->string('status')->toString() ?: 'active';

        $mahasiswa = User::query()
            ->when($status === 'archived', fn ($query) => $query->onlyTrashed())
            ->when($status === 'all', fn ($query) => $query->withTrashed())
            ->forRole('mahasiswa')
            ->withExists(['skripsi as has_running_skripsi' => fn ($query) => $query->where('current_phase', '!=', 'skripsi_selesai')])
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('nim', 'like', "%{$search}%");
                });
            })
            ->when(in_array($sort, ['name','email','nim'], true), fn ($query) => $query->orderBy($sort, $direction), fn ($query) => $query->latest())
            ->paginate(10)
            ->withQueryString();

        if ($request->ajax() || $request->expectsJson()) {
            return response()->json([
                'table_html' => view('kaprodi.mahasiswa.partials.table', ['mahasiswa' => $mahasiswa, 'sort' => $sort, 'direction' => $direction])->render(),
                'pagination_html' => view('kaprodi.mahasiswa.partials.pagination', ['mahasiswa' => $mahasiswa])->render(),
                'count_text' => $mahasiswa->total() . ' akun mahasiswa ditemukan.',
            ]);
        }

                $archivedCount = User::onlyTrashed()->forRole('mahasiswa')->count();

        return view('kaprodi.mahasiswa.index', $this->page('Master Mahasiswa', 'KAPRODI • MAHASISWA', [
            'mahasiswa' => $mahasiswa,
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
            'nim' => ['required', 'string', 'max:50', 'unique:users,nim'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        User::query()->create([
            'name' => $validated['name'],
            'nim' => $validated['nim'],
            'email' => $validated['email'],
            'password' => $validated['password'],
            'role' => 'mahasiswa',
        ]);

        return redirect()
            ->route('kaprodi.mahasiswa.index')
            ->with('success', 'Mahasiswa berhasil ditambahkan.');
    }

    public function show(User $mahasiswa): View
    {
        $this->ensureMahasiswa($mahasiswa);

        $skripsi = Skripsi::query()
            ->where('student_id', $mahasiswa->id)
            ->with(['assignments.lecturer', 'bimbingans' => fn ($query) => $query->latest('meeting_date')])
            ->latest()
            ->first();

        $primaryAdvisor = $skripsi?->assignments
            ?->firstWhere('role_type', 'pembimbing_1')
            ?->lecturer
            ?->name;

        $latestBimbingan = $skripsi?->bimbingans?->first();

        $skripsiData = [
            'record' => $skripsi,
            'topic' => $skripsi?->title ?? '-',
            'advisor' => $primaryAdvisor ?? '-',
            'guidance_count' => $skripsi?->bimbingans?->count() ?? 0,
            'last_guidance' => $latestBimbingan?->meeting_date?->format('d M Y') ?? '-',
            'status' => str($skripsi?->current_phase ?? '-')->replace(['_', '-'], ' ')->title(),
        ];

        $hasRunningSkripsi = $this->hasRunningSkripsi($mahasiswa);

        return view('kaprodi.mahasiswa.show', $this->page('Detail Mahasiswa', 'KAPRODI • MAHASISWA', [
            'mahasiswa' => $mahasiswa,
            'identity' => [
                'avatar' => collect(explode(' ', $mahasiswa->name))->map(fn(string $part): string => mb_substr($part, 0, 1))->take(2)->implode(''),
                'name' => $mahasiswa->name,
                'nim' => 'NIM: ' . ($mahasiswa->nim ?? '-'),
                'program' => 'Sistem Informasi',
                'status' => $mahasiswa->trashed() ? 'ARCHIVED' : strtoupper($mahasiswa->role),
            ],
            'skripsiData' => $skripsiData,
            'hasRunningSkripsi' => $hasRunningSkripsi,
        ]));
    }

    public function updateSkripsiStatus(Request $request, User $mahasiswa): RedirectResponse
    {
        $this->ensureMahasiswa($mahasiswa);

        $skripsi = Skripsi::query()->where('student_id', $mahasiswa->id)->latest()->first();

        if (! $skripsi) {
            return redirect()
                ->route('kaprodi.mahasiswa.show', $mahasiswa)
                ->with('error', 'Mahasiswa ini belum memiliki data skripsi.');
        }

        $validated = $request->validate([
            'current_phase' => ['required', Rule::in(['proposal', 'sidang_proposal', 'bimbingan_skripsi', 'sidang_skripsi', 'revisi_sidang_skripsi', 'review_dokumen_final', 'skripsi_selesai'])],
        ]);

        $skripsi->update([
            'current_phase' => $validated['current_phase'],
        ]);

        return redirect()
            ->route('kaprodi.mahasiswa.show', $mahasiswa)
            ->with('success', 'Fase skripsi mahasiswa berhasil diperbarui.');
    }

    public function update(Request $request, User $mahasiswa): RedirectResponse
    {
        $this->ensureMahasiswa($mahasiswa);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'nim' => ['required', 'string', 'max:50', Rule::unique('users', 'nim')->ignore($mahasiswa->id)],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($mahasiswa->id)],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
        ]);

        $mahasiswa->fill([
            'name' => $validated['name'],
            'nim' => $validated['nim'],
            'email' => $validated['email'],
        ]);

        if (! empty($validated['password'])) {
            $mahasiswa->password = Hash::make($validated['password']);
        }

        $mahasiswa->role = 'mahasiswa';
        $mahasiswa->save();

        return redirect()
            ->route('kaprodi.mahasiswa.show', $mahasiswa)
            ->with('success', 'Data mahasiswa berhasil diperbarui.');
    }

    public function archive(User $mahasiswa): RedirectResponse
    {
        $this->ensureMahasiswa($mahasiswa);

        if (! $this->hasRunningSkripsi($mahasiswa)) {
            return redirect()
                ->route('kaprodi.mahasiswa.show', $mahasiswa)
                ->with('error', 'Mahasiswa ini tidak memiliki skripsi berjalan. Gunakan hapus biasa.');
        }

        $mahasiswa->delete();

        return redirect()
            ->route('kaprodi.mahasiswa.index', ['status' => 'archived'])
            ->with('success', 'Mahasiswa berhasil diarsipkan.');
    }

    public function restore(int $id): RedirectResponse
    {
        $mahasiswa = User::withTrashed()->findOrFail($id);
        $this->ensureMahasiswa($mahasiswa);

        if (! $mahasiswa->trashed()) {
            return redirect()
                ->route('kaprodi.mahasiswa.show', $mahasiswa)
                ->with('error', 'Mahasiswa ini tidak sedang diarsipkan.');
        }

        $mahasiswa->restore();

        return redirect()
            ->route('kaprodi.mahasiswa.index')
            ->with('success', 'Mahasiswa berhasil dipulihkan.');
    }

    public function destroy(int $id): RedirectResponse
    {
        $mahasiswa = User::withTrashed()->findOrFail($id);
        $this->ensureMahasiswa($mahasiswa);

        if ($this->hasRunningSkripsi($mahasiswa)) {
            return redirect()
                ->route('kaprodi.mahasiswa.index', ['status' => $mahasiswa->trashed() ? 'archived' : 'active'])
                ->with('error', 'Mahasiswa dengan skripsi berjalan tidak bisa dihapus permanen. Pulihkan data jika diperlukan.');
        }

        $mahasiswa->forceDelete();

        return redirect()
            ->route('kaprodi.mahasiswa.index', ['status' => 'archived'])
            ->with('success', 'Mahasiswa berhasil dihapus permanen.');
    }

    /**
     * @param  array<string, mixed>  $extra
     * @return array<string, mixed>
     */
    private function page(string $heading, string $crumbs, array $extra = []): array
    {
        return $this->kaprodiPage($heading, $crumbs, $extra);
    }

    private function hasRunningSkripsi(User $mahasiswa): bool
    {
        return Skripsi::query()
            ->where('student_id', $mahasiswa->id)
            ->where('current_phase', '!=', 'skripsi_selesai')
            ->exists();
    }

    private function ensureMahasiswa(User $mahasiswa): void
    {
        abort_unless($mahasiswa->role === 'mahasiswa', 404);
    }
}
