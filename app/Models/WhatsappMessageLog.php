<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WhatsappMessageLog extends Model
{
    protected $guarded = [];

    protected $casts = ['sent_at' => 'datetime'];

    public function ticket()
    {
        return $this->belongsTo(Ticket::class);
    }
}
