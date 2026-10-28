<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SlaLog extends Model
{
    protected $guarded = [];

    protected $casts = ['escalated_at' => 'datetime'];

    public function ticket()
    {
        return $this->belongsTo(Ticket::class);
    }
}
