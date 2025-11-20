<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReconciliationIncomingEmail extends Model
{
    protected $fillable = [
        'request_id',
        'bank_id',
        'from_email',
        'from_name',
        'subject',
        'body',
        'html_body',
        'message_id',
        'received_at',
        'match_status',
        'match_notes',
        'status',
        'attachments',
    ];
    
    protected $casts = [
        'received_at' => 'datetime',
        'attachments' => 'array',
    ];
    
    public function request()
    {
        return $this->belongsTo(ReconciliationRequest::class);
    }
    
    public function bank()
    {
        return $this->belongsTo(ReconciliationBank::class);
    }
}
