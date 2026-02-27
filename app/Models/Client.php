<?php

namespace App\Models;

use App\Models\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Client extends Model
{
    use HasFactory;
    use BelongsToTenant;
    use SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'name',
        'email',
        'phone',
        'company',
        'notes',
        'is_active',
        'permanent_instructions',
    ];

    protected function casts(): array
    {
        return [
            'tenant_id'              => 'integer',
            'is_active'              => 'boolean',
            'permanent_instructions' => 'array',
        ];
    }

    public function isActive(): bool
    {
        return $this->is_active === true;
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function emails(): HasMany
    {
        return $this->hasMany(ClientEmail::class)->orderBy('sort_order');
    }
}
