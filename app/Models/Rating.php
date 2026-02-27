<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Rating extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia;

    protected $fillable = [
        'request_id',
        'user_id',
        'product_rating',
        'service_rating',
        'how_found_us',
        'customer_notes',
    ];

    protected $casts = [
        'product_rating' => 'integer',
        'service_rating' => 'integer',
    ];

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('rating_images');
    }

    public function request()
    {
        return $this->belongsTo(Request::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
