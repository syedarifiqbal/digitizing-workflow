<?php

namespace App\Models;

use App\Enums\RoleType;
use App\Models\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Commission extends Model
{
    use BelongsToTenant;
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'order_id',
        'user_id',
        'role_type',
        'base_amount',
        'extra_amount',
        'total_amount',
        'currency',
        'earned_on_status',
        'earned_at',
        'rule_snapshot',
        'is_paid',
        'paid_at',
        'notes',
    ];

    protected $casts = [
        'role_type' => RoleType::class,
        'base_amount' => 'decimal:2',
        'extra_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'earned_at' => 'datetime',
        'rule_snapshot' => 'array',
        'is_paid' => 'boolean',
        'paid_at' => 'datetime',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Convenience scopes
    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeUnpaid($query)
    {
        return $query->where('is_paid', false);
    }

    public function scopePaid($query)
    {
        return $query->where('is_paid', true);
    }

    public function scopeSales($query)
    {
        return $query->where('role_type', RoleType::SALES);
    }

    public function scopeDesigner($query)
    {
        return $query->where('role_type', RoleType::DESIGNER);
    }

    public function scopeEarnedBetween($query, $startDate, $endDate)
    {
        return $query->whereBetween('earned_at', [$startDate, $endDate]);
    }

    // Mark as paid
    public function markAsPaid(?string $notes = null): void
    {
        $this->update([
            'is_paid' => true,
            'paid_at' => now(),
            'notes' => $notes ? ($this->notes ? $this->notes . "\n" . $notes : $notes) : $this->notes,
        ]);
    }
}
