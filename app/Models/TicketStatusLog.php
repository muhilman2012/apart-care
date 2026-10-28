<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TicketStatusLog extends Model
{
    protected $guarded = [];

    protected $casts = ['changed_at' => 'datetime'];

    public function ticket()
    {
        return $this->belongsTo(Ticket::class);
    }

    public function changedBy()
    {
        return $this->belongsTo(User::class, 'changed_by_user_id');
    }
}
