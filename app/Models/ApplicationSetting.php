<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ApplicationSetting extends Model
{
    protected $fillable = ['application_name', 'logo_path'];

    public static function current(): self
    {
        return self::query()->firstOrCreate([], ['application_name' => 'TA Cloud UKWK']);
    }
}
