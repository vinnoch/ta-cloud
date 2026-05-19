<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DocumentTemplate extends Model
{
    protected $fillable = [
        'study_program_id',
        'nama',
        'is_published',
        'is_locked',
    ];

    protected $casts = [
        'is_published' => 'boolean',
        'is_locked' => 'boolean',
    ];

    protected $appends = [
        'name',
        'status',
        'can_modify',
    ];

    public function studyProgram(): BelongsTo
    {
        return $this->belongsTo(StudyProgram::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(DocumentTemplateItem::class)->orderBy('sort_order');
    }

    public function periodes(): BelongsToMany
    {
        return $this->belongsToMany(Periode::class, 'document_template_periode', 'document_template_id', 'periode_id')
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

    protected function status(): Attribute
    {
        return Attribute::make(
            get: fn () => ($this->is_locked ? 'locked' : ($this->is_published ? 'published' : 'draft')),
        );
    }

    protected function canModify(): Attribute
    {
        return Attribute::make(
            get: fn () => ! $this->is_locked,
        );
    }
}
