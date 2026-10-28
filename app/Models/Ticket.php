<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Ticket extends Model
{
    protected $guarded = [];

    protected $casts = ['target_sla_at' => 'datetime'];

    public function reporter()
    {
        return $this->belongsTo(User::class, 'reporter_user_id');
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }

    public function facility()
    {
        return $this->belongsTo(Facility::class);
    }

    public function qrLocation()
    {
        return $this->belongsTo(QrLocation::class);
    }

    public function workOrder()
    {
        return $this->hasOne(WorkOrder::class);
    }

    public function review()
    {
        return $this->hasOne(Review::class);
    }

    public function attachments()
    {
        return $this->hasMany(TicketAttachment::class);
    }

    public function statusLogs()
    {
        return $this->hasMany(TicketStatusLog::class);
    }

    public function slaLogs()
    {
        return $this->hasMany(SlaLog::class);
    }

    public function getReporterDisplayNameAttribute(): string
    {
        return $this->reporter?->name ?? $this->reporter_name ?? 'Pelapor anonim';
    }
}
