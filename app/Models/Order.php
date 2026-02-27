<?php

namespace App\Models;

use App\Enums\OrderPriority;
use App\Enums\OrderStatus;
use App\Enums\OrderType;
use App\Models\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Order extends Model
{
    use HasFactory;
    use SoftDeletes;
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'client_id',
        'created_by',
        'designer_id',
        'sales_user_id',
        'order_number',
        'po_number',
        'sequence',
        'type',
        'title',
        'is_quote',
        'instructions',
        'height',
        'width',
        'placement',
        'num_colors',
        'file_format',
        'patch_type',
        'quantity',
        'backing',
        'merrow_border',
        'fabric',
        'shipping_address',
        'need_by',
        'color_type',
        'vector_order_type',
        'required_format',
        'status',
        'priority',
        'due_at',
        'price',
        'is_invoiced',
        'invoiced_at',
        'currency',
        'source',
        'submitted_at',
        'approved_at',
        'delivered_at',
        'submitted_width',
        'submitted_height',
        'submitted_stitch_count',
        'parent_order_id',
    ];

    protected function casts(): array
    {
        return [
            'tenant_id'              => 'integer',
            'client_id'              => 'integer',
            'created_by'             => 'integer',
            'designer_id'            => 'integer',
            'sales_user_id'          => 'integer',
            'parent_order_id'        => 'integer',
            'due_at'                 => 'datetime',
            'need_by'                => 'date',
            'submitted_at'           => 'datetime',
            'approved_at'            => 'datetime',
            'delivered_at'           => 'datetime',
            'status'                 => OrderStatus::class,
            'priority'               => OrderPriority::class,
            'type'                   => OrderType::class,
            'is_quote'               => 'boolean',
            'is_invoiced'            => 'boolean',
            'invoiced_at'            => 'datetime',
            'quantity'               => 'integer',
            'num_colors'             => 'integer',
            'submitted_stitch_count' => 'integer',
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

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function designer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'designer_id');
    }

    public function sales(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sales_user_id');
    }

    public function files(): HasMany
    {
        return $this->hasMany(OrderFile::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'parent_order_id');
    }

    public function revisionOrders(): HasMany
    {
        return $this->hasMany(Order::class, 'parent_order_id')->latest();
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(OrderAssignment::class);
    }

    public function currentAssignment()
    {
        return $this->hasOne(OrderAssignment::class)->whereNull('ended_at')->latestOfMany('assigned_at');
    }

    public function statusHistory(): HasMany
    {
        return $this->hasMany(OrderStatusHistory::class)->orderByDesc('changed_at');
    }

    public function commissions(): HasMany
    {
        return $this->hasMany(Commission::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(OrderComment::class)->latest();
    }

    public function invoiceItems(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }

    public function deliveryOptions(): HasMany
    {
        return $this->hasMany(OrderDeliveryOption::class)->orderBy('sort_order');
    }

    /**
     * Generate the next sequence number and order number for a tenant.
     * Uses withTrashed() so soft-deleted orders don't cause unique constraint violations.
     */
    public static function nextOrderNumber(int $tenantId, string $prefix = ''): array
    {
        $sequence = ((int) static::withTrashed()->where('tenant_id', $tenantId)->max('sequence')) + 1;
        $prefix    = $prefix !== '' ? strtoupper($prefix) : 'ORD';
        $number    = sprintf('%s-%05d', $prefix, $sequence);

        return ['sequence' => $sequence, 'order_number' => $number];
    }
}
