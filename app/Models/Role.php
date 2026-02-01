<?php

namespace App\Models;

use App\Models\Traits\BelongsToTenant;
use Spatie\Permission\Models\Role as SpatieRole;

class Role extends SpatieRole
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'name',
        'guard_name',
    ];
}

