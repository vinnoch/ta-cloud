<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DocumentVersion extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'skripsi_id',
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

    public function skripsi(): BelongsTo
    {
        return $this->belongsTo(Skripsi::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function finalApprovals(): HasMany
    {
        return $this->hasMany(FinalDocumentApproval::class);
    }
}

