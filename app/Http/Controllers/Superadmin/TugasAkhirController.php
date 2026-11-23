<?php

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use App\Models\TugasAkhir;
use App\Services\TugasAkhirRouteService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TugasAkhirController extends Controller
{
    public function index(Request $request, TugasAkhirRouteService $routes): JsonResponse|View
    {
        $search = trim((string) $request->query('q', ''));
        $status = trim((string) $request->query('status', ''));
        $type = $routes->typeFromRequest($request);
        $label = $routes->label($type);
        $phaseLabels = [
            'proposal' => 'Proposal',
            'sidang_proposal' => 'Sidang Proposal',
            'bimbingan_skripsi' => $type === 'non_skripsi' ? 'Bimbingan Non-Skripsi' : 'Bimbingan Skripsi',
            'sidang_skripsi' => $type === 'non_skripsi' ? 'Sidang Non-Skripsi' : 'Sidang Skripsi',
            'revisi_sidang_skripsi' => $type === 'non_skripsi' ? 'Revisi Sidang Non-Skripsi' : 'Revisi Sidang Skripsi',
            'review_dokumen_final' => 'Review Dokumen Final',
            'skripsi_selesai' => $type === 'non_skripsi' ? 'Non-Skripsi Selesai' : 'Skripsi Selesai',
        ];

        $tugasAkhirs = $routes->scope(TugasAkhir::query(), $type)
            ->with(['student', 'periode'])
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($inner) use ($search): void {
                    $inner->where('title', 'like', "%{$search}%")
                        ->orWhereHas('student', fn ($student) => $student
                            ->where('name', 'like', "%{$search}%")
                            ->orWhere('nim', 'like', "%{$search}%"));
                });
            })
            ->when($status !== '', fn ($query) => $query->where('current_phase', $status))
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        $viewData = [
            'tugasAkhirs' => $tugasAkhirs,
            'search' => $search,
            'status' => $status,
            'phaseLabels' => $phaseLabels,
            'tugasAkhirType' => $type,
            'label' => $label,
        ];

        if ($request->ajax() || $request->expectsJson()) {
            return response()->json([
                'table_html' => view('superadmin.tugas-akhir.partials.table', $viewData)->render(),
                'pagination_html' => view('superadmin.tugas-akhir.partials.pagination', $viewData)->render(),
                'count_text' => $tugasAkhirs->total().' '.str($label)->lower().' ditemukan.',
            ]);
        }

        return view('superadmin.tugas-akhir.index', [
            'title' => $label,
            'heading' => $label,
            'crumbs' => 'SUPERADMIN • '.mb_strtoupper($label),
            ...$viewData,
        ]);
    }

    public function show(Request $request, TugasAkhir $skripsi, TugasAkhirRouteService $routes): View
    {
        $routes->ensureMatches($request, $skripsi);
        $skripsi->load(['student', 'periode', 'assignments.lecturer', 'documentVersions']);
        $label = $routes->label($request);

        return view('superadmin.tugas-akhir.show', [
            'title' => "Detail {$label}",
            'heading' => "Detail {$label}",
            'crumbs' => 'SUPERADMIN • '.mb_strtoupper($label),
            'skripsi' => $skripsi,
            'tugasAkhirType' => $routes->typeFromRequest($request),
        ]);
    }
}
