<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Skripsi extends Model
{
    use SoftDeletes;
    protected $table = 'skripsis';

    protected $casts = [
        'proposal_reviewed_at' => 'datetime',
    ];

    protected $fillable = [
        'student_id',
        'periode_id',
        'title',
        'type',
        'current_phase',
        'proposal_review_status',
        'proposal_reviewed_at',
        'proposal_review_note',
        'journal_article_url',
    ];

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
        return $this->belongsToMany(User::class, 'reviewer_assignments', 'skripsi_id', 'lecturer_id')
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

    public function grades(): HasMany
    {
        return $this->hasMany(Grade::class);
    }


    public function sidangRequests(): HasMany
    {
        return $this->hasMany(SidangRequest::class);
    }

    public function nonSkripsiRecord(): HasOne
    {
        return $this->hasOne(NonSkripsiRecord::class, 'skripsi_id');
    }

    public function finalDocumentApprovals(): HasMany
    {
        return $this->hasMany(FinalDocumentApproval::class);
    }
}
