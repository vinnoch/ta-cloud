<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Grade extends Model
{
    protected $table = 'grades';

    protected $fillable = [
        'skripsi_id',
        'format_penilaian_id',
        'reviewer_id',
        'role_type',
        'grade_event',
        'status',
        'score',
    ];

    public function skripsi(): BelongsTo
    {
        return $this->belongsTo(Skripsi::class);
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(FormatPenilaian::class, 'format_penilaian_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(GradeItem::class);
    }
}
