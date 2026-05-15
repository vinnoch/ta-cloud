<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ItemPenilaian extends Model
{
    protected $table = 'item_penilaians';

    protected $fillable = [
        'format_penilaian_id',
        'nama',
        'kode',
        'bobot',
        'sort_order',
    ];

    protected $appends = [
        'name',
        'code',
        'weight',
    ];

    public function format(): BelongsTo
    {
        return $this->belongsTo(FormatPenilaian::class, 'format_penilaian_id');
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

    protected function weight(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->attributes['bobot'] ?? null,
            set: fn (?int $value) => ['bobot' => $value],
        );
    }
}
