<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WorkOrder extends Model
{
    protected $guarded = [];

    protected $casts = ['dispatched_at' => 'datetime', 'started_at' => 'datetime', 'completed_at' => 'datetime'];

    public function ticket()
    {
        return $this->belongsTo(Ticket::class);
    }

    public function technician()
    {
        return $this->belongsTo(User::class, 'technician_user_id');
    }

    public function tro()
    {
        return $this->belongsTo(User::class, 'tro_user_id');
    }
}
