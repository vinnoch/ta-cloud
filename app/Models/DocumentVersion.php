<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentVersion extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'tugas_akhir_id',
        'phase',
        'version_number',
        'file_path',
        'mime_type',
        'size',
        'uploaded_by',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function tugasAkhir(): BelongsTo
    {
        return $this->belongsTo(TugasAkhir::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
