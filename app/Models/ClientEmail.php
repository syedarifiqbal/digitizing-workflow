<?php

namespace App\Models;

use App\Models\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClientEmail extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'client_id',
        'tenant_id',
        'email',
        'label',
        'is_primary',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'client_id' => 'integer',
            'tenant_id' => 'integer',
            'is_primary' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }
}
