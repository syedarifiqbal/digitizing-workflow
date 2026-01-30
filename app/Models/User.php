<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasFactory, Notifiable, HasRoles, SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'client_id',
        'name',
        'email',
        'phone',
        'password',
        'is_active',
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

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * Orders where this user is assigned as the designer.
     */
    public function designedOrders(): HasMany
    {
        return $this->hasMany(Order::class, 'designer_id');
    }

    /**
     * Orders where this user is assigned as the sales person.
     */
    public function salesOrders(): HasMany
    {
        return $this->hasMany(Order::class, 'sales_user_id');
    }

    /**
     * Commissions earned by this user.
     */
    public function commissions(): HasMany
    {
        return $this->hasMany(Commission::class, 'user_id');
    }

    public function isActive(): bool
    {
        return $this->is_active === true;
    }

    public function isAdmin(): bool
    {
        return $this->hasRole('Admin');
    }

    public function isManager(): bool
    {
        return $this->hasRole('Manager');
    }

    public function isDesigner(): bool
    {
        return $this->hasRole('Designer');
    }

    public function isClient(): bool
    {
        return $this->hasRole('Client');
    }
}
