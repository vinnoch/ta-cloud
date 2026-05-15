<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StudyProgram extends Model
{
    protected $fillable = [
        'department_id',
        'code',
        'name',
        'degree_level',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function gradingFormats(): HasMany
    {
        return $this->hasMany(FormatPenilaian::class);
    }
}
