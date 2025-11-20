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

    protected $casts = [
        'is_active' => 'boolean',
        'synced_at' => 'datetime',
    ];

    /** Firma bankaları */
    public function banks()
    {
        return $this->hasMany(CustomerBank::class);
    }

    /** Mutabakat talepleri */
    public function reconciliationRequests()
    {
        return $this->hasMany(ReconciliationRequest::class);
    }
}
