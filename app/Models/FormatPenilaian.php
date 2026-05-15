<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FormatPenilaian extends Model
{
    protected $table = 'format_penilaians';

    protected $fillable = [
        'study_program_id',
        'nama',
        'template_type',
        'is_published',
        'is_locked',
        'is_default',
    ];

    protected $casts = [
        'is_published' => 'boolean',
        'is_locked' => 'boolean',
        'is_default' => 'boolean',
    ];

    protected $appends = [
        'name',
        'format_type',
    ];

    public function studyProgram(): BelongsTo
    {
        return $this->belongsTo(StudyProgram::class);
    }

    public function periode(): BelongsTo
    {
        return $this->belongsTo(Periode::class, 'periode_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(ItemPenilaian::class, 'format_penilaian_id');
    }

    public function periodes(): BelongsToMany
    {
        return $this->belongsToMany(Periode::class, 'format_periode', 'format_penilaian_id', 'periode_id')
            ->withTimestamps();
    }

    public function periods(): BelongsToMany
    {
        return $this->periodes();
    }

    protected function name(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->attributes['nama'] ?? null,
            set: fn (?string $value) => ['nama' => $value],
        );
    }

    protected function formatType(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->attributes['template_type'] ?? null,
            set: fn (?string $value) => ['template_type' => $value],
        );
    }
}
