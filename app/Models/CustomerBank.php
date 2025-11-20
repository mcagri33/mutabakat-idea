<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomerBank extends Model
{
    protected $fillable = [
        'customer_id',
        'bank_name',
        'branch_name',
        'officer_name',
        'officer_email',
        'officer_phone',
        'is_active',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
}
