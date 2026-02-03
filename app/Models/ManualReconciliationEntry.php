<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ManualReconciliationEntry extends Model
{
    protected $table = 'manual_reconciliation_entries';

    protected $fillable = [
        'customer_id',
        'bank_name',
        'branch_name',
        'year',
        'requested_at',
        'reply_received_at',
        'notes',
    ];

    protected $casts = [
        'requested_at'      => 'date',
        'reply_received_at' => 'date',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
}
