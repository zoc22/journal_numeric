<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class TenantUser extends Pivot
{
    protected $table = 'tenant_user';
    protected $fillable = ['tenant_id', 'user_id', 'role_in_tenant'];
}