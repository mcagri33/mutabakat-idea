<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReconciliationBank extends Model
{
    protected $fillable = [
        'request_id',
        'customer_bank_id',
        'customer_id', // 🔥 direkt burada
        'bank_name',
        'branch_name',
        'officer_name',
        'officer_email',
        'officer_phone',

        'mail_status',
        'reply_status',

        'mail_sent_at',
        'reply_received_at',

        'notes',
    ];

    protected $casts = [
        'mail_sent_at'       => 'datetime',
        'reply_received_at'  => 'datetime',
    ];

    /** Talep ilişkisi */
    public function request()
    {
        return $this->belongsTo(ReconciliationRequest::class, 'request_id');
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    /** Firma banka tanımı (kaynak kayıt) */
    public function sourceBank()
    {
        return $this->belongsTo(CustomerBank::class, 'customer_bank_id');
    }

    /** Bankaya yüklenen belgeler */
    public function documents()
    {
        return $this->hasMany(ReconciliationDocument::class, 'bank_id');
    }

    /** Gönderilen mail logları */
    public function emails()
    {
        return $this->hasMany(ReconciliationEmail::class, 'bank_id');
    }
    
    /** Gelen mail'ler */
    public function incomingEmails()
    {
        return $this->hasMany(ReconciliationIncomingEmail::class, 'bank_id');
    }
}
