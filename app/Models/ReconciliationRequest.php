<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReconciliationRequest extends Model
{
    protected $fillable = [
        'customer_id',
        'type',
        'year',
        'month',
        'status',
        'requested_at',
        'sent_at',
        'received_at',
        'notes',
        'attachments',
        'cc_emails',
        'include_auto_cc',
    ];

    protected $casts = [
        'requested_at' => 'datetime',
        'sent_at'      => 'datetime',
        'received_at'  => 'datetime',
        'attachments'  => 'array',
        'include_auto_cc' => 'boolean',
    ];

    /** Firma ilişkisi */
    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    /** Mutabakat içindeki bankalar */
    public function banks()
    {
        return $this->hasMany(ReconciliationBank::class, 'request_id');
    }

    public function documents()
    {
        return $this->hasManyThrough(
            ReconciliationDocument::class,
            ReconciliationBank::class,
            'request_id', // ReconciliationBank.request_id
            'bank_id',    // ReconciliationDocument.bank_id
            'id',         // ReconciliationRequest.id
            'id'          // ReconciliationBank.id
        );
    }
    
    /** Mutabakatın mail logları */
    public function emails()
    {
        return $this->hasMany(ReconciliationEmail::class, 'request_id');
    }
    
    /** Gelen mail'ler */
    public function incomingEmails()
    {
        return $this->hasMany(ReconciliationIncomingEmail::class, 'request_id');
    }
}
