<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentSubmission extends Model
{
    protected $fillable = [
        'skripsi_id',
        'document_template_item_id',
        'document_version_id',
        'notes',
    ];

    public function skripsi(): BelongsTo
    {
        return $this->belongsTo(Skripsi::class);
    }

    public function templateItem(): BelongsTo
    {
        return $this->belongsTo(DocumentTemplateItem::class, 'document_template_item_id');
    }

    public function documentVersion(): BelongsTo
    {
        return $this->belongsTo(DocumentVersion::class);
    }
}
