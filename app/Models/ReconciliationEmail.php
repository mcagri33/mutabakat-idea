<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReconciliationEmail extends Model
{
     protected $fillable = [
        'request_id',
        'bank_id',
        'sent_to',
        'subject',
        'body',
        'status',
        'sent_at',
        'error_message',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
    ];

    public function request()
    {
        return $this->belongsTo(ReconciliationRequest::class, 'request_id');
    }

    public function bank()
    {
        return $this->belongsTo(ReconciliationBank::class, 'bank_id');
    }
}
