<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TechnicianHoliday extends Model
{
    protected $fillable = ['technician_id', 'start_date', 'end_date', 'reason'];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    public function technician()
    {
        return $this->belongsTo(User::class, 'technician_id');
    }

    public function scopeActive($query)
    {
        return $query->where('end_date', '>=', today());
    }

    public function scopeOverlapsDate($query, \Carbon\Carbon $date)
    {
        return $query->where('start_date', '<=', $date->toDateString())
                     ->where('end_date', '>=', $date->toDateString());
    }
}
