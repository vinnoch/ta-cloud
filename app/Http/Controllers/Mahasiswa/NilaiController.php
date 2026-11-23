<?php

namespace App\Http\Controllers\Mahasiswa;

use App\Http\Controllers\Controller;
use App\Models\Grade;
use App\Models\TugasAkhir;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NilaiController extends Controller
{
    public function index(Request $request, TugasAkhir $skripsi): View
    {
        if ($skripsi->student_id !== $request->user()->id) {
            abort(403);
        }

        $grades = Grade::query()
            ->where('tugas_akhir_id', $skripsi->id)
            ->with(['template', 'reviewer', 'items.itemPenilaian'])
            ->orderByDesc('id')
            ->get();

        return view('mahasiswa.nilai.index', [
            'title' => 'Nilai',
            'heading' => 'Nilai',
            'crumbs' => 'MAHASISWA • NILAI',
            'skripsi' => $skripsi,
            'grades' => $grades,
            'proposalFinalSubmission' => FinalSubmissionController::buildSubmissionState($skripsi, 'sidang_proposal'),
            'skripsiFinalSubmission' => FinalSubmissionController::buildSubmissionState($skripsi, 'sidang_skripsi'),
        ]);
    }
}
