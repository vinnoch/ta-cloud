<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NonSkripsiRecord extends Model
{
    use SoftDeletes;
    protected $fillable = [
        'skripsi_id',
        'summary',
        'abstract',
        'report_path',
        'publication_url',
        'final_score',
    ];

    public function skripsi(): BelongsTo
    {
        return $this->belongsTo(Skripsi::class);
    }
}
