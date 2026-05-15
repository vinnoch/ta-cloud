<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Periode extends Model
{
    use SoftDeletes;

    protected $table = 'periodes';

    protected $fillable = [
        'tahun_akademik_id',
        'kode_periode',
        'semester',
        'sk_nomor',
        'sk_dokumen_url',
        'tgl_mulai',
        'tgl_selesai',
        'is_aktif',
        'status',
    ];

    protected $casts = [
        'tgl_mulai' => 'date',
        'tgl_selesai' => 'date',
        'is_aktif' => 'boolean',
    ];

    public function tahunAkademik(): BelongsTo
    {
        return $this->belongsTo(TahunAkademik::class, 'tahun_akademik_id');
    }

    public function formats(): BelongsToMany
    {
        return $this->belongsToMany(FormatPenilaian::class, 'format_periode', 'periode_id', 'format_penilaian_id')
            ->withTimestamps();
    }

    public function skripsis(): HasMany
    {
        return $this->hasMany(Skripsi::class, 'periode_id');
    }

    public function getNameAttribute(): string
    {
        $yearName = $this->tahunAkademik?->name ?? $this->kode_periode;
        $semesterName = (int) $this->semester === 1 ? 'Ganjil' : 'Genap';

        return trim("{$yearName} {$semesterName}");
    }
}
