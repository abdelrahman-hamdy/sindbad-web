<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TechnicianLocation extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'technician_id',
        'latitude',
        'longitude',
        'heading',
        'speed',
        'is_online',
        'recorded_at',
        'updated_at',
    ];

    protected $casts = [
        'latitude' => 'float',
        'longitude' => 'float',
        'heading' => 'float',
        'speed' => 'float',
        'is_online' => 'boolean',
        'recorded_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function technician()
    {
        return $this->belongsTo(User::class, 'technician_id');
    }
}
