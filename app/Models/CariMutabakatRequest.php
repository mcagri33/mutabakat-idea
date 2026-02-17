<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CariMutabakatRequest extends Model
{
    protected $table = 'cari_mutabakat_requests';

    protected $fillable = [
        'customer_id',
        'year',
        'month',
        'status',
        'requested_at',
        'sent_at',
        'created_by',
        'notes',
    ];

    protected $casts = [
        'requested_at' => 'datetime',
        'sent_at' => 'datetime',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function items()
    {
        return $this->hasMany(CariMutabakatItem::class, 'request_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
