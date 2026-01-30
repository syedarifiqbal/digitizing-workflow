<?php

namespace App\Models;

use App\Models\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderComment extends Model
{
    use BelongsToTenant;
    protected $fillable = [
        'tenant_id',
        'order_id',
        'user_id',
        'visibility',
        'body',
        'created_at',
        'updated_at',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeClientVisible($query)
    {
        return $query->where('visibility', 'client');
    }
}
