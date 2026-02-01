<?php

namespace App\Models;

use App\Models\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class WebhookLog extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'event',
        'url',
        'payload',
        'response_status',
        'response_body',
        'attempts',
        'success',
    ];

    protected $casts = [
        'payload' => 'array',
        'success' => 'boolean',
        'attempts' => 'integer',
        'response_status' => 'integer',
    ];

    public function scopeFailed($query)
    {
        return $query->where('success', false);
    }

    public function scopeSuccessful($query)
    {
        return $query->where('success', true);
    }
}
