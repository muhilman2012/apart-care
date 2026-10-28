<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Facility extends Model
{
    protected $guarded = [];

    public function getLocationLabelAttribute(): string
    {
        return $this->facility_name;
    }
}
