<?php

namespace App\Models;

use App\Enums\CommissionType;
use App\Enums\RoleType;
use App\Models\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CommissionRule extends Model
{
    use BelongsToTenant;
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'user_id',
        'role_type',
        'type',
        'fixed_amount',
        'percent_rate',
        'currency',
        'is_active',
    ];

    protected $casts = [
        'tenant_id'    => 'integer',
        'user_id'      => 'integer',
        'role_type'    => RoleType::class,
        'type'         => CommissionType::class,
        'fixed_amount' => 'decimal:2',
        'percent_rate' => 'decimal:2',
        'is_active'    => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Backwards compatibility alias
    public function salesUser(): BelongsTo
    {
        return $this->user();
    }
}
