<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
     protected $fillable = [
        'external_id',
        'uuid',
        'name',
        'email',
        'company',
        'phone',
        'is_active',
        'synced_at',
    ];
}
