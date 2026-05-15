<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GradeItem extends Model
{
    protected $table = 'grade_items';

    protected $fillable = [
        'grade_id',
        'item_penilaian_id',
        'score',
    ];

    public function grade(): BelongsTo
    {
        return $this->belongsTo(Grade::class);
    }

    public function itemPenilaian(): BelongsTo
    {
        return $this->belongsTo(ItemPenilaian::class);
    }
}
