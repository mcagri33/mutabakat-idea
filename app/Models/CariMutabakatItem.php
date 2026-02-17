<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class CariMutabakatItem extends Model
{
    protected $table = 'cari_mutabakat_items';

    protected $fillable = [
        'request_id',
        'hesap_tipi',
        'referans',
        'cari_kodu',
        'unvan',
        'email',
        'cc_email',
        'tel_no',
        'vergi_no',
        'tarih',
        'bakiye_tipi',
        'bakiye',
        'pb',
        'karsiligi',
        'token',
        'reply_status',
        'reply_received_at',
        'mail_status',
    ];

    protected $casts = [
        'tarih' => 'date',
        'bakiye' => 'decimal:2',
        'karsiligi' => 'decimal:2',
        'reply_received_at' => 'datetime',
    ];

    public function request()
    {
        return $this->belongsTo(CariMutabakatRequest::class);
    }

    public function reply()
    {
        return $this->hasOne(CariMutabakatReply::class, 'item_id');
    }

    public static function generateToken(): string
    {
        do {
            $token = Str::random(48);
        } while (self::where('token', $token)->exists());

        return $token;
    }
}
