<?php

namespace App\Http\Controllers\Kaprodi;

use App\Http\Controllers\Controller;
use App\Models\TahunAkademik;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TahunAkademikController extends Controller
{
    use BuildsKaprodiPage;
    public function index(Request $request): View|\Illuminate\Http\JsonResponse
    {
        $search = $request->string('q')->toString();
        $sort = $request->string('sort')->toString();
        $direction = strtolower($request->string('direction')->toString()) === 'asc' ? 'asc' : 'desc';

        $tahunAkademik = TahunAkademik::query()
            ->withExists(['periodes as has_periods'])
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('tahun_awal', 'like', "%{$search}%")->orWhere('tahun_akhir', 'like', "%{$search}%");
                });
            })
            ->when($sort === 'name', fn ($query) => $query->orderBy('tahun_awal', $direction))
            ->when($sort === 'rentang', fn ($query) => $query->orderBy('tahun_awal', $direction))
            ->when(! in_array($sort, ['name','rentang'], true), fn ($query) => $query->latest())
            ->paginate(10)
            ->withQueryString();

        if ($request->ajax() || $request->expectsJson()) {
            return response()->json([
                'table_html' => view('kaprodi.tahun-akademik.partials.table', ['tahunAkademik' => $tahunAkademik, 'sort' => $sort, 'direction' => $direction])->render(),
                'pagination_html' => view('kaprodi.tahun-akademik.partials.pagination', ['tahunAkademik' => $tahunAkademik])->render(),
                'count_text' => $tahunAkademik->total() . ' tahun akademik ditemukan.',
            ]);
        }

        return view('kaprodi.tahun-akademik.index', $this->page('Master Tahun Akademik', 'KAPRODI • TAHUN AKADEMIK', [
            'tahunAkademik' => $tahunAkademik,
            'search' => $search,
            'sort' => $sort,
            'direction' => $direction,
            'sideCards' => [],
        ]));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            
            'tahun_awal' => ['required', 'integer', 'digits:4'],
            'tahun_akhir' => ['required', 'integer', 'digits:4'],
        ]);

        TahunAkademik::query()->create([
            
            'tahun_awal' => $validated['tahun_awal'],
            'tahun_akhir' => $validated['tahun_akhir'],
        ]);

        return redirect()
            ->route('kaprodi.tahun-akademik.index')
            ->with('success', 'Tahun akademik berhasil ditambahkan.');
    }

    public function show(TahunAkademik $tahunAkademik): View
    {
        $hasPeriods = $this->hasPeriods($tahunAkademik);

        return view('kaprodi.tahun-akademik.show', $this->page('Detail Tahun Akademik', 'KAPRODI • TAHUN AKADEMIK', [
            'tahunAkademik' => $tahunAkademik,
            'hasPeriods' => $hasPeriods,
            'identity' => [
                'avatar' => substr($tahunAkademik->name, -4),
                'name' => $tahunAkademik->name,
                'period' => "Tahun {$tahunAkademik->tahun_awal} - {$tahunAkademik->tahun_akhir}",
            ],
        ]));
    }

    public function update(Request $request, TahunAkademik $tahunAkademik): RedirectResponse
    {
        $validated = $request->validate([
            'tahun_awal' => ['required', 'integer', 'digits:4'],
            'tahun_akhir' => ['required', 'integer', 'digits:4'],
        ]);

        $tahunAkademik->fill([
            
            'tahun_awal' => $validated['tahun_awal'],
            'tahun_akhir' => $validated['tahun_akhir'],
        ]);

        $tahunAkademik->save();

        return redirect()->back()->with('success', 'Data tahun akademik berhasil diperbarui.');
    }

    public function archive(TahunAkademik $tahunAkademik): RedirectResponse
    {
        if (! $this->hasPeriods($tahunAkademik)) {
            return redirect()
                ->route('kaprodi.tahun-akademik.show', $tahunAkademik)
                ->with('error', 'Tahun akademik ini tidak memiliki periode terkait. Gunakan hapus biasa.');
        }

        $tahunAkademik->delete();

        return redirect()
            ->route('kaprodi.tahun-akademik.index')
            ->with('success', 'Tahun akademik berhasil diarsipkan.');
    }

    public function destroy(TahunAkademik $tahunAkademik): RedirectResponse
    {
        if ($this->hasPeriods($tahunAkademik)) {
            return redirect()
                ->route('kaprodi.tahun-akademik.show', $tahunAkademik)
                ->with('error', 'Tahun akademik dengan periode terkait tidak bisa dihapus. Gunakan arsipkan.');
        }

        $tahunAkademik->delete();

        return redirect()
            ->route('kaprodi.tahun-akademik.index')
            ->with('success', 'Tahun akademik berhasil dihapus.');
    }

    private function hasPeriods(TahunAkademik $tahunAkademik): bool
    {
        return $tahunAkademik->periodes()->exists();
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
