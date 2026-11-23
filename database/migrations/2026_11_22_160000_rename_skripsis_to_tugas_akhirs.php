<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * @var array<string, array{foreign: string, indexes: array<string, string>}>
     */
    private array $relations = [
        'bimbingans' => [
            'foreign' => 'bimbingans_skripsi_id_foreign',
            'indexes' => [
                'bimbingans_skripsi_id_foreign' => 'bimbingans_tugas_akhir_id_foreign',
            ],
        ],
        'document_submissions' => [
            'foreign' => 'document_submissions_skripsi_id_foreign',
            'indexes' => [
                'document_submissions_skripsi_id_document_template_item_id_unique' => 'document_submissions_tugas_akhir_item_unique',
            ],
        ],
        'document_versions' => [
            'foreign' => 'document_versions_skripsi_id_foreign',
            'indexes' => [
                'document_versions_skripsi_id_phase_version_number_unique' => 'document_versions_tugas_akhir_phase_version_unique',
            ],
        ],
        'grades' => [
            'foreign' => 'grades_skripsi_id_foreign',
            'indexes' => [],
        ],
        'reviewer_assignments' => [
            'foreign' => 'reviewer_assignments_skripsi_id_foreign',
            'indexes' => [
                'reviewer_assignments_skripsi_id_role_type_index' => 'reviewer_assignments_tugas_akhir_role_index',
                'reviewer_assignments_skripsi_id_role_type_unique' => 'reviewer_assignments_tugas_akhir_role_unique',
            ],
        ],
        'sidang_requests' => [
            'foreign' => 'sidang_requests_skripsi_id_foreign',
            'indexes' => [
                'sidang_requests_skripsi_id_lecturer_id_unique' => 'sidang_requests_tugas_akhir_lecturer_unique',
            ],
        ],
    ];

    public function up(): void
    {
        if (Schema::hasTable('skripsis') && ! Schema::hasTable('tugas_akhirs')) {
            Schema::rename('skripsis', 'tugas_akhirs');
        }

        $this->renameIndexes('tugas_akhirs', [
            'skripsis_student_id_unique' => 'tugas_akhirs_student_id_unique',
            'skripsis_periode_id_current_phase_index' => 'tugas_akhirs_periode_phase_index',
            'skripsis_current_phase_index' => 'tugas_akhirs_current_phase_index',
            'skripsis_proposal_review_status_index' => 'tugas_akhirs_proposal_review_status_index',
        ]);

        foreach ($this->relations as $table => $relation) {
            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'skripsi_id')) {
                continue;
            }

            if (DB::getDriverName() === 'mysql') {
                Schema::table($table, fn (Blueprint $blueprint) => $blueprint->dropForeign($relation['foreign']));
            }

            Schema::table($table, fn (Blueprint $blueprint) => $blueprint->renameColumn('skripsi_id', 'tugas_akhir_id'));
            $this->renameIndexes($table, $relation['indexes']);

            if (DB::getDriverName() === 'mysql') {
                Schema::table($table, function (Blueprint $blueprint) use ($table): void {
                    $blueprint->foreign('tugas_akhir_id', "{$table}_tugas_akhir_id_foreign")
                        ->references('id')
                        ->on('tugas_akhirs')
                        ->cascadeOnDelete();
                });
            }
        }

        Schema::dropIfExists('non_skripsi_records');
    }

    public function down(): void
    {
        foreach (array_reverse($this->relations, true) as $table => $relation) {
            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'tugas_akhir_id')) {
                continue;
            }

            if (DB::getDriverName() === 'mysql') {
                Schema::table($table, fn (Blueprint $blueprint) => $blueprint->dropForeign("{$table}_tugas_akhir_id_foreign"));
            }

            $this->renameIndexes($table, array_flip($relation['indexes']));
            Schema::table($table, fn (Blueprint $blueprint) => $blueprint->renameColumn('tugas_akhir_id', 'skripsi_id'));

            if (DB::getDriverName() === 'mysql') {
                Schema::table($table, function (Blueprint $blueprint) use ($relation): void {
                    $blueprint->foreign('skripsi_id', $relation['foreign'])
                        ->references('id')
                        ->on('tugas_akhirs')
                        ->cascadeOnDelete();
                });
            }
        }

        $this->renameIndexes('tugas_akhirs', [
            'tugas_akhirs_student_id_unique' => 'skripsis_student_id_unique',
            'tugas_akhirs_periode_phase_index' => 'skripsis_periode_id_current_phase_index',
            'tugas_akhirs_current_phase_index' => 'skripsis_current_phase_index',
            'tugas_akhirs_proposal_review_status_index' => 'skripsis_proposal_review_status_index',
        ]);

        if (Schema::hasTable('tugas_akhirs') && ! Schema::hasTable('skripsis')) {
            Schema::rename('tugas_akhirs', 'skripsis');
        }

        if (! Schema::hasTable('non_skripsi_records')) {
            Schema::create('non_skripsi_records', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('skripsi_id')->unique()->constrained('skripsis')->cascadeOnDelete();
                $table->text('summary');
                $table->text('abstract');
                $table->string('report_path')->nullable();
                $table->string('publication_url')->nullable();
                $table->double('final_score')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }
    }

    /**
     * @param  array<string, string>  $renames
     */
    private function renameIndexes(string $table, array $renames): void
    {
        if (DB::getDriverName() !== 'mysql' || ! Schema::hasTable($table)) {
            return;
        }

        $indexes = collect(Schema::getIndexes($table))->pluck('name')->all();

        foreach ($renames as $from => $to) {
            if (in_array($from, $indexes, true) && ! in_array($to, $indexes, true)) {
                Schema::table($table, fn (Blueprint $blueprint) => $blueprint->renameIndex($from, $to));
                $indexes[] = $to;
            }
        }
    }
};
