<?php

namespace App\Models;

use App\Models\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderDeliveryOption extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'order_id',
        'tenant_id',
        'label',
        'width',
        'height',
        'stitch_count',
        'price',
        'currency',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'order_id'    => 'integer',
            'tenant_id'   => 'integer',
            'stitch_count' => 'integer',
            'price'       => 'decimal:2',
            'sort_order'  => 'integer',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
