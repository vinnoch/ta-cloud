<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class TahunAkademik extends Model
{
    use SoftDeletes;

    protected $table = 'tahun_akademiks';

    protected $fillable = [
        'tahun_awal',
        'tahun_akhir',
    ];

    public function getNameAttribute(): string
    {
        return "{$this->tahun_awal}/{$this->tahun_akhir}";
    }

    public function periodes(): HasMany
    {
        return $this->hasMany(Periode::class, 'tahun_akademik_id');
    }
}
