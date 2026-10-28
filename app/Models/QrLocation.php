<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class QrLocation extends Model
{
    protected $guarded = [];

    protected $casts = [
        'is_active' => 'boolean',
        'last_inspected_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (QrLocation $location): void {
            $location->uuid ??= (string) Str::uuid();
        });
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function tickets()
    {
        return $this->hasMany(Ticket::class);
    }

    public function tower()
    {
        return $this->belongsTo(Tower::class);
    }

    public function getReferenceImageUrlAttribute(): ?string
    {
        if (! $this->reference_image_path) {
            return null;
        }

        return str_starts_with($this->reference_image_path, 'images/')
            ? asset($this->reference_image_path)
            : asset('storage/'.$this->reference_image_path);
    }

    public function getLocationLabelAttribute(): string
    {
        return $this->name;
    }
}
