<?php

namespace App\Services;

use App\Models\Skripsi;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

class StudentDocumentPathService
{
    public function buildStoragePath(Skripsi $skripsi, string $phase, int $version, UploadedFile $file): string
    {
        $student = $skripsi->student;
        $studentKey = $this->studentKey($student?->nim, $skripsi->student_id);
        $directory = 'documents/mahasiswa/' . $studentKey . '/skripsi-' . $skripsi->id . '/' . $this->phaseDirectory($phase);
        $extension = strtolower((string) $file->getClientOriginalExtension());
        $timestamp = now()->format('YmdHis');
        $filename = Str::slug($phase, '_') . '_v' . $version . '_' . $timestamp . '.' . $extension;

        return $directory . '/' . $filename;
    }

    private function studentKey(?string $nim, int $studentId): string
    {
        $nim = trim((string) $nim);

        if ($nim !== '') {
            return preg_replace('/[^A-Za-z0-9_-]/', '', $nim) ?: ('mahasiswa-' . $studentId);
        }

        return 'mahasiswa-' . $studentId;
    }

    private function phaseDirectory(string $phase): string
    {
        return match ($phase) {
            'proposal', 'proposal_final' => 'proposal',
            'skripsi_final' => 'skripsi-final',
            default => str_contains($phase, 'bimbingan') || str_contains($phase, 'revisi') || str_contains($phase, 'revision')
                ? 'revisi'
                : Str::slug($phase, '-'),
        };
    }
}
