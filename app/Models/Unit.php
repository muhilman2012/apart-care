<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Unit extends Model
{
    protected $guarded = [];

    public function tenant()
    {
        return $this->belongsTo(User::class, 'tenant_user_id');
    }

    public function getLocationLabelAttribute(): string
    {
        return "Unit {$this->unit_code}";
    }
}
