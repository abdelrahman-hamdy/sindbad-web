<?php

namespace App\Models;

use App\Enums\UserRole;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements FilamentUser
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'password',
        'odoo_id',
        'is_active',
        'role',
        'fcm_token',
        'invoice_number',
        'quotation_template',
        'profile_link',
        'avatar_url',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->role === UserRole::Admin->value;
    }

    public function requests()
    {
        return $this->hasMany(Request::class);
    }

    public function assignedRequests()
    {
        return $this->hasMany(Request::class, 'technician_id');
    }

    public function appNotifications()
    {
        return $this->hasMany(Notification::class, 'recipient_id');
    }

    public function manualOrders()
    {
        return $this->hasMany(ManualOrder::class);
    }

    public function location()
    {
        return $this->hasOne(TechnicianLocation::class, 'technician_id');
    }

    public function scopeAdmins($query)
    {
        return $query->where('role', UserRole::Admin->value);
    }

    public function scopeTechnicians($query)
    {
        return $query->where('role', UserRole::Technician->value);
    }

    public function scopeCustomers($query)
    {
        return $query->where('role', UserRole::Customer->value);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function isAdmin(): bool
    {
        return $this->role === UserRole::Admin->value;
    }

    public function isTechnician(): bool
    {
        return $this->role === UserRole::Technician->value;
    }

    public function isCustomer(): bool
    {
        return $this->role === UserRole::Customer->value;
    }
}
