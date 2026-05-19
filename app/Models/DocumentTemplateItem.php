<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentTemplateItem extends Model
{
    protected $fillable = [
        'document_template_id',
        'nama',
        'kode',
        'is_required',
        'sort_order',
    ];

    protected $casts = [
        'is_required' => 'boolean',
    ];

    protected $appends = [
        'name',
        'code',
    ];

    public function template(): BelongsTo
    {
        return $this->belongsTo(DocumentTemplate::class, 'document_template_id');
    }

    protected function name(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->attributes['nama'] ?? null,
            set: fn (?string $value) => ['nama' => $value],
        );
    }

    protected function code(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->attributes['kode'] ?? null,
            set: fn (?string $value) => ['kode' => $value],
        );
    }
}
