<?php

namespace App\Console\Commands;

use App\Models\Grade;
use App\Models\Skripsi;
use App\Services\NotificationService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('app:isi-nilai-reminder-command')]
#[Description('Send grading reminders when sidang skripsi time is reached')]
class IsiNilaiReminderCommand extends Command
{
    public function handle(NotificationService $notifications): int
    {
        $skripsis = Skripsi::query()
            ->with(['student', 'assignments.lecturer'])
            ->where('current_phase', 'sidang_skripsi')
            ->whereNotNull('sidang_skripsi_datetime')
            ->where('sidang_skripsi_datetime', '<=', now())
            ->whereNull('sidang_skripsi_grade_notified_at')
            ->get();

        $sentCount = 0;

        foreach ($skripsis as $skripsi) {
            $assignments = $skripsi->assignments
                ->whereIn('role_type', ['pembimbing_1', 'pembimbing_2', 'penguji_1', 'penguji_2'])
                ->values();

            foreach ($assignments as $assignment) {
                $lecturer = $assignment->lecturer;

                if (! $lecturer) {
                    continue;
                }

                $alreadyPublished = Grade::query()
                    ->where('skripsi_id', $skripsi->id)
                    ->where('reviewer_id', $lecturer->id)
                    ->where('role_type', $assignment->role_type)
                    ->where('grade_event', 'sidang_skripsi')
                    ->where('status', 'published')
                    ->exists();

                if ($alreadyPublished) {
                    continue;
                }

                $notifications->send([$lecturer], [
                    'type' => 'sidang_skripsi_grading_due',
                    'title' => 'Isi Nilai Sidang Skripsi',
                    'message' => "Waktu sidang skripsi {$skripsi->student?->name} sudah tiba. Silakan isi nilai sidang skripsi.",
                    'url' => route('dosen.skripsi.show', $skripsi, false),
                    'meta' => [
                        'skripsi_id' => $skripsi->id,
                        'role_type' => $assignment->role_type,
                        'scheduled_at' => optional($skripsi->sidang_skripsi_datetime)->toIso8601String(),
                    ],
                ]);

                $sentCount++;
            }

            $skripsi->forceFill([
                'sidang_skripsi_grade_notified_at' => now(),
            ])->save();
        }

        $this->info("Sent {$sentCount} grading reminder notifications.");

        return self::SUCCESS;
    }
}
