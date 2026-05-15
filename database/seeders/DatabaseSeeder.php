<?php

namespace Database\Seeders;

use App\Models\FormatPenilaian;
use App\Models\Periode;
use App\Models\Skripsi;
use App\Models\StudyProgram;
use App\Models\TahunAkademik;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $password = Hash::make('password');

        $this->seedSamplePdfFiles();

        DB::table('departments')->updateOrInsert(
            ['code' => 'FTI'],
            ['name' => 'Fakultas Teknologi Informasi', 'updated_at' => now(), 'created_at' => now()]
        );

        $departmentId = DB::table('departments')->where('code', 'FTI')->value('id');

        DB::table('study_programs')->updateOrInsert(
            ['code' => 'SI'],
            ['department_id' => $departmentId, 'name' => 'Sistem Informasi', 'degree_level' => 'S1', 'is_active' => true, 'updated_at' => now(), 'created_at' => now()]
        );

        DB::table('study_programs')->updateOrInsert(
            ['code' => 'TI'],
            ['department_id' => $departmentId, 'name' => 'Teknik Informatika', 'degree_level' => 'S1', 'is_active' => true, 'updated_at' => now(), 'created_at' => now()]
        );

        User::query()->updateOrCreate(
            ['email' => 'kaprodi@tacloud.test'],
            ['name' => 'Kaprodi Sistem Informasi', 'password' => $password, 'role' => 'kaprodi', 'study_program_id' => DB::table('study_programs')->where('code', 'SI')->value('id')]
        );

        $lecturerData = [
            ['name' => 'Dr. Sarah Wijaya', 'email' => 'sarah.wijaya@tacloud.test', 'nidn_nip' => '0412345678'],
            ['name' => 'Dr. Bima Prakoso', 'email' => 'dosen2@tacloud.test', 'nidn_nip' => '198709102019031001'],
            ['name' => 'Dr. Retno Ayu Lestari', 'email' => 'dosen3@tacloud.test', 'nidn_nip' => '0412345679'],
            ['name' => 'Dr. Taufik Hidayat', 'email' => 'dosen4@tacloud.test', 'nidn_nip' => '198709102019031002'],
            ['name' => 'Dr. Dimas Wicaksono', 'email' => 'dosen5@tacloud.test', 'nidn_nip' => '0412345680'],
            ['name' => 'Dr. Nabila Maharani', 'email' => 'dosen6@tacloud.test', 'nidn_nip' => '198709102019031003'],
            ['name' => 'Dr. Yoga Permana', 'email' => 'dosen7@tacloud.test', 'nidn_nip' => '0412345681'],
            ['name' => 'Dr. Citra Handayani', 'email' => 'dosen8@tacloud.test', 'nidn_nip' => '198709102019031004'],
        ];

        foreach ($lecturerData as $lecturer) {
            User::query()->updateOrCreate(
                ['email' => $lecturer['email']],
                array_merge($lecturer, ['password' => $password, 'role' => 'dosen', 'study_program_id' => DB::table('study_programs')->where('code', 'SI')->value('id')])
            );
        }

        $studentNames = [
            'Nadia Permata Salsabila', 'Adrian Sterling', 'Ahmad Fikri Ramadhan', 'Nabila Putri Maharani',
            'Rizky Aditya Nugraha', 'Salsa Nur Aini', 'Muhammad Daffa Pratama', 'Intan Permata Sari',
            'Bagas Saputra Wijaya', 'Cindy Aurelia Putri', 'Farhan Akbar Maulana', 'Dinda Ayu Lestari',
            'Rafi Alghifari', 'Nadya Safitri Hapsari', 'Galih Pranowo', 'Maya Oktaviani',
            'Kevin Ardian Saputra', 'Putri Kirana Dewi', 'Yoga Firmansyah', 'Tasya Rahmawati',
            'Luthfi Ananda', 'Annisa Zahra', 'Fajar Nurhadi', 'Naufal Rizqullah', 'Cahya Puspita',
        ];

        foreach ($studentNames as $index => $name) {
            $email = ($name === 'Nadia Permata Salsabila') ? 'mahasiswa.baru@tacloud.test' : 
                     (($name === 'Adrian Sterling') ? 'adrian.sterling@tacloud.test' : 'mhs' . ($index + 1) . '@tacloud.test');
            
            $nim = '202108' . str_pad((string)($index + 1), 5, '0', STR_PAD_LEFT);
            if ($name === 'Adrian Sterling') $nim = '20210800001';
            if ($name === 'Nadia Permata Salsabila') $nim = '20210800025';

            User::query()->updateOrCreate(
                ['email' => $email],
                ['name' => $name, 'nim' => $nim, 'password' => $password, 'role' => 'mahasiswa', 'study_program_id' => DB::table('study_programs')->where('code', 'SI')->value('id')]
            );
        }

        $tahunAkademik = TahunAkademik::query()->updateOrCreate(['tahun_awal' => 2025, 'tahun_akhir' => 2026], []);
        $periode = Periode::query()->updateOrCreate(['kode_periode' => '20251'], [
            'tahun_akademik_id' => $tahunAkademik->id, 'semester' => 1, 'sk_nomor' => 'SK/FTI/2025/001',
            'tgl_mulai' => '2025-08-01', 'tgl_selesai' => '2026-01-31', 'is_aktif' => true, 'status' => 'active',
        ]);

        $studyProgram = StudyProgram::query()->where('code', 'SI')->firstOrFail();

        $formatDefinitions = [
            'sidang_proposal' => [
                'nama' => 'Format Penilaian Sidang Proposal',
                'items' => [
                    ['nama' => 'Penulisan Proposal', 'kode' => 'penulisan_proposal', 'bobot' => 30],
                    ['nama' => 'Presentasi Proposal', 'kode' => 'presentasi_proposal', 'bobot' => 20],
                    ['nama' => 'Penguasaan Masalah', 'kode' => 'penguasaan_masalah', 'bobot' => 50],
                ],
            ],
            'sidang_skripsi' => [
                'nama' => 'Format Penilaian Sidang Skripsi',
                'items' => [
                    ['nama' => 'Penulisan Skripsi', 'kode' => 'penulisan_skripsi', 'bobot' => 30],
                    ['nama' => 'Presentasi', 'kode' => 'presentasi', 'bobot' => 20],
                    ['nama' => 'Penguasaan Materi', 'kode' => 'penguasaan_materi', 'bobot' => 50],
                ],
            ],
        ];

        foreach ($formatDefinitions as $templateType => $definition) {
            $format = FormatPenilaian::query()->updateOrCreate(
                ['study_program_id' => $studyProgram->id, 'template_type' => $templateType, 'is_default' => true],
                ['nama' => $definition['nama'], 'is_published' => true, 'is_locked' => false]
            );
            $format->periodes()->syncWithoutDetaching([$periode->id]);
            $format->items()->delete();
            foreach ($definition['items'] as $index => $item) {
                $format->items()->create(array_merge($item, ['sort_order' => $index + 1]));
            }
        }

        $proposalFormatId = FormatPenilaian::query()->where('template_type', 'sidang_proposal')->value('id');
        $skripsiFormatId = FormatPenilaian::query()->where('template_type', 'sidang_skripsi')->value('id');
        $dosenUsers = User::query()->forRole('dosen')->orderBy('id')->get();

        $titles = [
            'Analisis Kesiapan Implementasi ERP pada UMKM Distribusi Pangan',
            'Perancangan Dashboard Kinerja Layanan Helpdesk Berbasis SLA',
            'Analisis Kepuasan Pengguna Sistem Akademik dengan Metode EUCS',
            'Rancang Bangun Sistem Monitoring Progress Skripsi Berbasis Web',
            'Evaluasi Tata Kelola TI Menggunakan COBIT 2019 pada Perguruan Tinggi',
            'Perancangan Data Warehouse untuk Pelaporan Akademik Fakultas',
            'Analisis Adopsi E-Wallet Mahasiswa Menggunakan Model UTAUT',
            'Sistem Pendukung Keputusan Penentuan Dosen Pembimbing dengan AHP',
            'Analisis Risiko Keamanan Informasi pada Sistem PMB Online',
            'Rancang Bangun Knowledge Management System untuk Unit Akademik',
            'Analisis Kualitas Layanan Aplikasi Mobile Kampus Menggunakan SERVQUAL',
            'Perancangan Sistem Approval Dokumen Skripsi Berbasis Workflow',
            'Analisis Retensi Mahasiswa Menggunakan Data Mining',
            'Rancang Bangun Chatbot Layanan Akademik Berbasis NLP',
            'Analisis Kematangan Transformasi Digital pada Biro Administrasi',
            'Perancangan Enterprise Architecture Sistem Skripsi dengan TOGAF',
            'Sistem Rekomendasi Topik Skripsi Berbasis Minat Mahasiswa',
            'Analisis Efektivitas LMS dalam Mendukung Pembelajaran Hybrid',
            'Rancang Bangun Sistem Inventaris Laboratorium Berbasis QR Code',
            'Analisis User Experience Portal Mahasiswa Menggunakan HEART',
            'Implementasi Business Intelligence untuk Visualisasi Penjualan',
            'Audit Keamanan Jaringan Menggunakan Framework NIST',
            'Perancangan Sistem E-Procurement Berbasis Cloud Computing',
            'Analisis Sentimen Opini Publik Terhadap Layanan Kampus di Twitter',
        ];

        $phases = ['proposal', 'sidang_proposal', 'bimbingan_skripsi', 'sidang_skripsi', 'revisi_sidang_skripsi', 'review_dokumen_final', 'skripsi_selesai'];

        $activeStudents = User::query()
            ->forRole('mahasiswa')
            ->where('email', '!=', 'mahasiswa.baru@tacloud.test')
            ->orderBy('id')
            ->get();

        foreach ($activeStudents as $index => $student) {
            $phase = $phases[$index % count($phases)];
            $title = $titles[$index % count($titles)];

            $skripsi = Skripsi::query()->updateOrCreate(
                ['student_id' => $student->id],
                ['periode_id' => $periode->id, 'title' => $title, 'type' => 'skripsi', 'current_phase' => $phase]
            );

            DB::table('document_versions')->updateOrInsert(
                ['skripsi_id' => $skripsi->id, 'phase' => 'proposal', 'version_number' => 1],
                ['file_path' => 'dokumen/sample.pdf', 'mime_type' => 'application/pdf', 'size' => 582, 'uploaded_by' => $student->id, 'created_at' => now()->subDays(30)]
            );

            if (in_array($phase, ['bimbingan_skripsi', 'sidang_skripsi', 'revisi_sidang_skripsi', 'review_dokumen_final', 'skripsi_selesai'], true)) {
                DB::table('reviewer_assignments')->updateOrInsert(
                    ['skripsi_id' => $skripsi->id, 'lecturer_id' => $dosenUsers[0]->id, 'role_type' => 'pembimbing_1'],
                    ['created_at' => now(), 'updated_at' => now()]
                );
                DB::table('reviewer_assignments')->updateOrInsert(
                    ['skripsi_id' => $skripsi->id, 'lecturer_id' => $dosenUsers[1]->id, 'role_type' => 'pembimbing_2'],
                    ['created_at' => now(), 'updated_at' => now()]
                );
                DB::table('bimbingans')->updateOrInsert(
                    ['skripsi_id' => $skripsi->id, 'reviewer_id' => $dosenUsers[0]->id, 'meeting_date' => now()->subDays(7)->toDateString()],
                    ['phase' => 'bimbingan_skripsi', 'lecturer_notes' => 'Lanjutkan revisi.', 'revision_file_url' => '/storage/revisi-bimbingan/sample/revisi-bimbingan-1.pdf', 'created_at' => now()]
                );
            }

            if (in_array($phase, ['sidang_proposal', 'bimbingan_skripsi', 'sidang_skripsi', 'revisi_sidang_skripsi', 'review_dokumen_final', 'skripsi_selesai'], true) && $proposalFormatId) {
                DB::table('grades')->updateOrInsert(
                    ['skripsi_id' => $skripsi->id, 'format_penilaian_id' => $proposalFormatId, 'reviewer_id' => $dosenUsers[2]->id, 'grade_event' => 'sidang_proposal'],
                    ['role_type' => 'penguji_1', 'status' => ($phase === 'proposal' ? 'draft' : 'final'), 'score' => 85, 'created_at' => now()]
                );
            }

            if (in_array($phase, ['sidang_skripsi', 'revisi_sidang_skripsi', 'review_dokumen_final', 'skripsi_selesai'], true)) {
                DB::table('reviewer_assignments')->updateOrInsert(
                    ['skripsi_id' => $skripsi->id, 'lecturer_id' => $dosenUsers[2]->id, 'role_type' => 'penguji_1'],
                    ['created_at' => now()]
                );
                DB::table('reviewer_assignments')->updateOrInsert(
                    ['skripsi_id' => $skripsi->id, 'lecturer_id' => $dosenUsers[3]->id, 'role_type' => 'penguji_2'],
                    ['created_at' => now()]
                );
                if ($skripsiFormatId) {
                    DB::table('grades')->updateOrInsert(
                        ['skripsi_id' => $skripsi->id, 'format_penilaian_id' => $skripsiFormatId, 'reviewer_id' => $dosenUsers[2]->id, 'grade_event' => 'sidang_skripsi'],
                        ['role_type' => 'penguji_1', 'status' => ($phase === 'sidang_skripsi' ? 'draft' : 'final'), 'score' => 88, 'created_at' => now()]
                    );
                }
            }
        }
    }

    private function seedSamplePdfFiles(): void
    {
        $pdf = <<<'PDF'
%PDF-1.1
1 0 obj
<< /Type /Catalog /Pages 2 0 R >>
endobj
2 0 obj
<< /Type /Pages /Kids [3 0 R] /Count 1 >>
endobj
3 0 obj
<< /Type /Page /Parent 2 0 R /MediaBox [0 0 300 144] /Contents 4 0 R /Resources << /Font << /F1 5 0 R >> >> >>
endobj
4 0 obj
<< /Length 44 >>
stream
BT
/F1 24 Tf
72 72 Td
(Sample PDF) Tj
ET
endstream
endobj
5 0 obj
<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>
endobj
xref
0 6
0000000000 65535 f 
0000000010 00000 n 
0000000063 00000 n 
0000000118 00000 n 
0000000244 00000 n 
0000000338 00000 n 
trailer
<< /Root 1 0 R /Size 6 >>
startxref
408
%%EOF
PDF;

        Storage::disk('public')->put('dokumen/sample.pdf', $pdf);
        Storage::disk('public')->put('revisi-bimbingan/sample/revisi-bimbingan-1.pdf', $pdf);
    }
}
