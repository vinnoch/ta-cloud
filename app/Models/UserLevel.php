<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserLevel extends Model
{
    protected $table = 'users_level';

    protected $primaryKey = 'users_id';

    protected $fillable = ['users_level'];

    public function users()
    {
        return $this->hasMany(User::class, 'users_id', 'users_id');
    }
}
