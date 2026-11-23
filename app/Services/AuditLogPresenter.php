<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Bimbingan;
use App\Models\DocumentSubmission;
use App\Models\DocumentTemplate;
use App\Models\DocumentTemplateItem;
use App\Models\DocumentVersion;
use App\Models\FormatPenilaian;
use App\Models\Grade;
use App\Models\GradeItem;
use App\Models\ItemPenilaian;
use App\Models\Periode;
use App\Models\ReviewerAssignment;
use App\Models\SidangRequest;
use App\Models\StudyProgram;
use App\Models\TahunAkademik;
use App\Models\TugasAkhir;
use App\Models\User;
use App\Models\UserLevel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class AuditLogPresenter
{
    public const TOPICS = [
        'Keamanan' => [
            'actions' => ['%auth%', '%login%', '%logout%', '%security%', 'superadmin.reauth%', 'superadmin.bootstrapped'],
            'targets' => [],
        ],
        'Akun' => [
            'actions' => ['user.%', 'registration.%', 'registrations.%', 'register.%', 'profile.%', 'role-selection.%', 'kaprodi.dosen.%', 'kaprodi.mahasiswa.%', 'kaprodi.import.%'],
            'targets' => [User::class, UserLevel::class],
        ],
        'Bimbingan' => [
            'actions' => ['%bimbingan%'],
            'targets' => [Bimbingan::class],
        ],
        'Dokumen' => [
            'actions' => ['%document%', '%dokumen%', '%final.submit%', '%storage%', '%upload%'],
            'targets' => [DocumentVersion::class, DocumentSubmission::class, DocumentTemplate::class, DocumentTemplateItem::class],
        ],
        'Penilaian' => [
            'actions' => ['%penilaian%', '%nilai%', '%formats.%', '%grades.%'],
            'targets' => [FormatPenilaian::class, ItemPenilaian::class, Grade::class, GradeItem::class],
        ],
        'Sidang' => [
            'actions' => ['%sidang%'],
            'targets' => [SidangRequest::class],
        ],
        'Tugas Akhir' => [
            'actions' => ['%tugas-akhir%', '%tugas_akhir%', '%skripsi%', '%proposal%', '%final-review%', '%reviewer%', '%non-skripsi%'],
            'targets' => [TugasAkhir::class, ReviewerAssignment::class],
        ],
        'Akademik' => [
            'actions' => ['%periode%', '%tahun-akademik%', '%program-studi%'],
            'targets' => [Periode::class, TahunAkademik::class, StudyProgram::class],
        ],
        'Pengaturan' => [
            'actions' => ['settings.%', 'audit.%'],
            'targets' => [],
        ],
        'Aktivitas Lain' => ['actions' => [], 'targets' => []],
    ];

    public function present(AuditLog $log): array
    {
        $actor = ($log->relationLoaded('actor') ? $log->actor?->name : null) ?? $log->actor_email ?? 'Sistem';
        $target = $this->targetLabel($log);
        $title = match ($log->action) {
            'user.invited' => 'Pengguna diundang',
            'user.roles_changed', 'user.role_changed' => 'Peran pengguna diubah',
            'user.deactivated' => 'Pengguna dinonaktifkan',
            'user.reactivated' => 'Pengguna diaktifkan kembali',
            'user.permanently_deleted' => 'Pengguna dihapus permanen',
            'settings.updated' => 'Pengaturan aplikasi diperbarui',
            'registration.approved' => 'Pendaftaran disetujui',
            'registration.rejected' => 'Pendaftaran ditolak',
            'superadmin.reauthenticated' => 'Verifikasi ulang berhasil',
            'superadmin.reauth_failed', 'superadmin.reauth_rejected' => 'Verifikasi ulang ditolak',
            'audit.retention_updated' => 'Masa penyimpanan log diperbarui',
            'audit.purged' => 'Log sistem dihapus',
            'role-selection.store' => 'Pilihan peran disimpan',
            default => $this->representativeTitle($log),
        };

        return [
            'title' => $title,
            'summary' => $this->summary($log, $actor, $target, $title),
            'category' => $this->topicFor($log),
            'actor' => $actor,
            'target' => $target,
            'before' => PrivilegedAudit::sanitize($log->before ?? []),
            'after' => PrivilegedAudit::sanitize($log->after ?? []),
            'ip_address' => $log->ip_address,
            'reference' => $log->target_type && $log->target_id
                ? class_basename($log->target_type).' #'.$log->target_id
                : 'Log #'.$log->id,
        ];
    }

    private function summary(AuditLog $log, string $actor, string $target, string $title): string
    {
        if (in_array($log->action, ['user.roles_changed', 'user.role_changed'], true)) {
            $before = $this->displayValue($log->before['roles'] ?? $log->before['role'] ?? null);
            $after = $this->displayValue($log->after['roles'] ?? $log->after['role'] ?? null);

            return "{$actor} mengubah peran {$target} dari {$before} menjadi {$after}.";
        }

        return match ($log->action) {
            'settings.updated' => "{$actor} memperbarui nama aplikasi atau logo.",
            'user.deactivated' => "Akun {$target} dinonaktifkan oleh {$actor}.",
            'user.reactivated' => "Akun {$target} diaktifkan kembali oleh {$actor}.",
            'user.permanently_deleted' => "Akun {$target} dihapus permanen oleh {$actor}.",
            'audit.retention_updated' => "{$actor} mengatur penyimpanan log menjadi ".($log->after['retention_days'] ?? 90).' hari.',
            'audit.purged' => "{$actor} menghapus ".($log->after['deleted_count'] ?? 0).' log sistem.',
            default => "{$actor}: {$title}".($target !== 'Sistem' ? " — {$target}." : '.'),
        };
    }

    private function targetLabel(AuditLog $log): string
    {
        if (! $log->target_type || ! $log->target_id) {
            return 'Sistem';
        }

        $target = $log->relationLoaded('target') ? $log->target : null;

        return $target?->name
            ?? $target?->title
            ?? class_basename($log->target_type).' #'.$log->target_id;
    }

    public function constrainToTopic(Builder $query, string $topic): void
    {
        $earlierRules = [];

        foreach (self::TOPICS as $name => $rules) {
            if ($name !== $topic) {
                $earlierRules[] = $rules;

                continue;
            }

            if ($rules['actions'] !== [] || $rules['targets'] !== []) {
                $query->where(fn (Builder $query) => $this->applyTopicMatch($query, $rules));
            }

            foreach ($earlierRules as $earlierRule) {
                $query->whereNot(fn (Builder $query) => $this->applyTopicMatch($query, $earlierRule));
            }

            if ($rules['actions'] === [] && $rules['targets'] === []) {
                foreach (self::TOPICS as $knownRules) {
                    if ($knownRules['actions'] !== [] || $knownRules['targets'] !== []) {
                        $query->whereNot(fn (Builder $query) => $this->applyTopicMatch($query, $knownRules));
                    }
                }
            }

            return;
        }
    }

    private function topicFor(AuditLog $log): string
    {
        foreach (self::TOPICS as $topic => $rules) {
            if (($rules['actions'] === [] && $rules['targets'] === [])
                || in_array($log->target_type, $rules['targets'], true)
                || collect($rules['actions'])->contains(fn (string $pattern): bool => Str::is(str_replace('%', '*', $pattern), $log->action))) {
                return $topic;
            }
        }

        return 'Aktivitas Lain';
    }

    /** @param array{actions: array<int, string>, targets: array<int, class-string>} $rules */
    private function applyTopicMatch(Builder $query, array $rules): void
    {
        foreach ($rules['actions'] as $index => $pattern) {
            $query->{$index === 0 ? 'where' : 'orWhere'}('action', 'like', $pattern);
        }

        if ($rules['targets'] !== []) {
            $query->orWhereIn('target_type', $rules['targets']);
        }
    }

    private function representativeTitle(AuditLog $log): string
    {
        $subject = $this->subjectFor($log);
        $verb = match (true) {
            $log->action === 'model.created', Str::endsWith($log->action, ['.store', '.submit']) => 'disimpan',
            $log->action === 'model.updated', Str::endsWith($log->action, '.update') => 'diubah',
            $log->action === 'model.deleted', Str::endsWith($log->action, ['.destroy', '.permanently-destroy']) => 'dihapus',
            $log->action === 'model.restored', Str::endsWith($log->action, '.restore') => 'dipulihkan',
            Str::endsWith($log->action, '.approve') => 'disetujui',
            Str::endsWith($log->action, '.reject') => 'ditolak',
            Str::endsWith($log->action, '.publish') => 'diterbitkan',
            Str::endsWith($log->action, '.archive') => 'diarsipkan',
            Str::endsWith($log->action, '.duplicate') => 'diduplikasi',
            Str::endsWith($log->action, '.unlock') => 'dibuka kembali',
            default => null,
        };

        return $verb
            ? "{$subject} {$verb}"
            : Str::of($log->action)->replace(['.', '_', '-'], ' ')->headline()->toString();
    }

    private function subjectFor(AuditLog $log): string
    {
        $modelSubjects = [
            TugasAkhir::class => 'Tugas Akhir',
            ReviewerAssignment::class => 'Penugasan reviewer',
            Bimbingan::class => 'Bimbingan',
            DocumentVersion::class => 'Versi dokumen',
            DocumentSubmission::class => 'Pengumpulan dokumen',
            DocumentTemplate::class => 'Template dokumen',
            DocumentTemplateItem::class => 'Item dokumen',
            FormatPenilaian::class => 'Format penilaian',
            ItemPenilaian::class => 'Item penilaian',
            Grade::class => 'Penilaian',
            GradeItem::class => 'Nilai',
            SidangRequest::class => 'Permohonan sidang',
            Periode::class => 'Periode',
            TahunAkademik::class => 'Tahun akademik',
            StudyProgram::class => 'Program studi',
            User::class => 'Akun',
            UserLevel::class => 'Hak akses',
        ];

        if (isset($modelSubjects[$log->target_type])) {
            return $modelSubjects[$log->target_type];
        }

        foreach ([
            '*bimbingan*' => 'Bimbingan',
            '*penilaian*' => 'Penilaian',
            '*nilai*' => 'Nilai',
            '*sidang*' => 'Permohonan sidang',
            '*document*' => 'Dokumen',
            '*dokumen*' => 'Dokumen',
            '*proposal*' => 'Proposal',
            '*tugas-akhir*' => 'Tugas Akhir',
            '*tugas_akhir*' => 'Tugas Akhir',
            '*skripsi*' => 'Tugas Akhir',
            '*non-skripsi*' => 'Tugas Akhir',
            '*periode*' => 'Periode',
            '*tahun-akademik*' => 'Tahun akademik',
            '*registration*' => 'Pendaftaran',
            '*register*' => 'Pendaftaran',
            '*profile*' => 'Profil',
            '*role-selection*' => 'Pilihan peran',
            '*dosen*' => 'Akun dosen',
            '*mahasiswa*' => 'Akun mahasiswa',
        ] as $pattern => $subject) {
            if (Str::is($pattern, $log->action)) {
                return $subject;
            }
        }

        return 'Aktivitas';
    }

    private function displayValue(mixed $value): string
    {
        if (is_array($value)) {
            return collect($value)->map(fn ($item) => Str::headline((string) $item))->join(', ');
        }

        return $value ? Str::headline((string) $value) : 'Tidak ada';
    }
}
