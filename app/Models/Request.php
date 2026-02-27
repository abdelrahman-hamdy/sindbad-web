<?php

namespace App\Models;

use App\Enums\RequestStatus;
use App\Enums\RequestType;
use App\Enums\ServiceType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Request extends Model implements HasMedia
{
    use HasFactory, SoftDeletes, LogsActivity, InteractsWithMedia;

    protected $table = 'requests';

    protected $fillable = [
        'type',
        'user_id',
        'technician_id',
        'status',
        'invoice_number',
        'address',
        'latitude',
        'longitude',
        'scheduled_at',
        'end_date',
        'completed_at',
        'technician_accepted_at',
        'task_start_time',
        'task_end_time',
        // Service
        'service_type',
        'description',
        'details',
        // Installation
        'product_type',
        'quantity',
        'is_site_ready',
        'readiness_details',
    ];

    protected $casts = [
        'type' => RequestType::class,
        'status' => RequestStatus::class,
        'service_type' => ServiceType::class,
        'details' => 'array',
        'readiness_details' => 'array',
        'scheduled_at' => 'date',
        'end_date' => 'date',
        'completed_at' => 'datetime',
        'technician_accepted_at' => 'datetime',
        'task_start_time' => 'datetime',
        'task_end_time' => 'datetime',
        'latitude' => 'float',
        'longitude' => 'float',
        'is_site_ready' => 'boolean',
        'quantity' => 'integer',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['status', 'technician_id'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('attachments')->acceptsMimeTypes([
            'image/jpeg', 'image/png', 'image/jpg', 'image/webp',
        ]);

        $this->addMediaCollection('technician_images')->acceptsMimeTypes([
            'image/jpeg', 'image/png', 'image/jpg', 'image/webp',
        ]);
    }

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function technician()
    {
        return $this->belongsTo(User::class, 'technician_id');
    }

    public function rating()
    {
        return $this->hasOne(Rating::class);
    }

    // Business helpers
    public function isService(): bool
    {
        return $this->type === RequestType::Service;
    }

    public function isInstallation(): bool
    {
        return $this->type === RequestType::Installation;
    }

    public function isCompleted(): bool
    {
        return $this->status === RequestStatus::Completed;
    }

    public function hasRating(): bool
    {
        return $this->rating()->exists();
    }

    public function canBeCompletedByTechnician(): bool
    {
        return ! $this->hasRating();
    }

    public function getInvoicePrefixAttribute(): string
    {
        return $this->isService() ? 'T-' : 'B-';
    }
}
