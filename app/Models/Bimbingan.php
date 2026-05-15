<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class Bimbingan extends Model
{
    protected $fillable = [
        'skripsi_id',
        'reviewer_id',
        'phase',
        'meeting_date',
        'student_notes',
        'lecturer_notes',
        'reviewed_version_id',
        'revision_file_url',
    ];

    protected $casts = [
        'meeting_date' => 'date',
    ];

    public function skripsi(): BelongsTo
    {
        return $this->belongsTo(Skripsi::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }

    public function reviewedVersion(): BelongsTo
    {
        return $this->belongsTo(DocumentVersion::class, 'reviewed_version_id');
    }

    public function getRevisionFileUrlAttribute($value): ?string
    {
        if (is_string($value) && trim($value) !== '') {
            return $value;
        }

        if (! $this->relationLoaded('reviewedVersion')) {
            $this->loadMissing('reviewedVersion');
        }

        $documentId = $this->reviewedVersion?->id;

        return $documentId ? route('documents.preview', $documentId) : null;
    }

    public function getHasRevisionFileAttribute(): bool
    {
        $url = $this->revision_file_url;

        if (! is_string($url) || trim($url) === '') {
            return false;
        }

        $documentPath = $this->reviewedVersion?->file_path;

        return is_string($documentPath) && trim($documentPath) !== '' && Storage::disk('local')->exists($documentPath);
    }
}
