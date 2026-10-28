<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Building extends Model
{
    protected $guarded = [];

    protected $casts = ['is_active' => 'boolean'];

    public function towers()
    {
        return $this->hasMany(Tower::class);
    }
}
