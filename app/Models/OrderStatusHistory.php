<?php

namespace App\Models;

use App\Enums\OrderStatus;
use App\Models\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderStatusHistory extends Model
{
    use BelongsToTenant;
    protected $table = 'order_status_history';

    protected $fillable = [
        'tenant_id',
        'order_id',
        'from_status',
        'to_status',
        'changed_by_user_id',
        'changed_at',
        'notes',
    ];

    protected $casts = [
        'from_status' => OrderStatus::class,
        'to_status' => OrderStatus::class,
        'changed_at' => 'datetime',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by_user_id');
    }
}
