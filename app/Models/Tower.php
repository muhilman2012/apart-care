<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Tower extends Model
{
    protected $guarded = [];

    protected $casts = ['is_active' => 'boolean'];

    public function building()
    {
        return $this->belongsTo(Building::class);
    }

    public function qrLocations()
    {
        return $this->hasMany(QrLocation::class);
    }
}
