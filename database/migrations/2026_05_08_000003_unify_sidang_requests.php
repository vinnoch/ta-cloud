<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $skripsis = DB::table('skripsis')
            ->whereIn('current_phase', ['proposal', 'sidang_proposal', 'bimbingan_skripsi', 'sidang_skripsi', 'revisi_sidang_skripsi', 'review_dokumen_final', 'skripsi_selesai'])
            ->get();

        foreach ($skripsis as $skripsi) {
            $status = 'submitted';
            if ($skripsi->proposal_review_status === 'approved' || in_array($skripsi->current_phase, ['sidang_proposal', 'bimbingan_skripsi', 'sidang_skripsi', 'revisi_sidang_skripsi', 'review_dokumen_final', 'skripsi_selesai'])) {
                $status = 'approved';
            } elseif ($skripsi->proposal_review_status === 'revision_required') {
                $status = 'rejected';
            }

            DB::table('sidang_requests')->updateOrInsert(
                [
                    'skripsi_id' => $skripsi->id,
                    'role_type' => 'mahasiswa',
                ],
                [
                    'lecturer_id' => $skripsi->student_id,
                    'status' => $status,
                    'note' => $skripsi->proposal_review_note,
                    'submitted_at' => DB::raw('(SELECT MIN(created_at) FROM document_versions WHERE skripsi_id = ' . $skripsi->id . ' AND phase = "proposal")'),
                    'approved_at' => $skripsi->proposal_reviewed_at,
                    'approved_by' => null, // We don't have kaprodi ID stored on skripsi
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }

    public function down(): void
    {
        DB::table('sidang_requests')->where('role_type', 'mahasiswa')->delete();
    }
};
