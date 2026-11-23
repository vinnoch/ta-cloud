<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class TugasAkhir extends Model
{
    use SoftDeletes;

    protected $table = 'tugas_akhirs';

    protected $casts = [
        'final_submitted_at' => 'datetime',
        'proposal_reviewed_at' => 'datetime',
        'proposal_rejected_at' => 'datetime',
        'sidang_proposal_datetime' => 'datetime',
        'sidang_proposal_grade_notified_at' => 'datetime',
        'sidang_tugas_akhir_datetime' => 'datetime',
        'sidang_tugas_akhir_grade_notified_at' => 'datetime',
    ];

    protected $fillable = [
        'student_id',
        'periode_id',
        'title',
        'type',
        'current_phase',
        'proposal_review_status',
        'proposal_reviewed_at',
        'proposal_rejected_at',
        'proposal_review_note',
        'journal_article_url',
        'sidang_proposal_datetime',
        'sidang_proposal_grade_notified_at',
        'sidang_tugas_akhir_datetime',
        'sidang_tugas_akhir_grade_notified_at',
    ];

    public function scopeSkripsi(Builder $query): Builder
    {
        return $query->where($query->qualifyColumn('type'), 'skripsi');
    }

    public function scopeNonSkripsi(Builder $query): Builder
    {
        return $query->where($query->qualifyColumn('type'), 'non_skripsi');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function periode(): BelongsTo
    {
        return $this->belongsTo(Periode::class);
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(ReviewerAssignment::class);
    }

    public function reviewers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'reviewer_assignments', 'tugas_akhir_id', 'lecturer_id')
            ->withPivot('role_type')
            ->withTimestamps();
    }

    public function bimbingans(): HasMany
    {
        return $this->hasMany(Bimbingan::class);
    }

    public function documentVersions(): HasMany
    {
        return $this->hasMany(DocumentVersion::class);
    }

    public function documentSubmissions(): HasMany
    {
        return $this->hasMany(DocumentSubmission::class);
    }

    public function grades(): HasMany
    {
        return $this->hasMany(Grade::class);
    }

    public function sidangRequests(): HasMany
    {
        return $this->hasMany(SidangRequest::class);
    }

    public function isProposalPhase(): bool
    {
        return in_array($this->current_phase, ['proposal', 'sidang_proposal'], true);
    }

    public function isDraft(): bool
    {
        return $this->proposal_review_status === 'draft';
    }

    public function isRejected(): bool
    {
        return $this->proposal_review_status === 'rejected';
    }

    public function isApproved(): bool
    {
        return $this->proposal_review_status === 'approved';
    }

    public function isProcessing(): bool
    {
        return in_array($this->proposal_review_status, [null, 'pending', 'published'], true)
            && $this->proposal_review_status !== 'draft';
    }
}
