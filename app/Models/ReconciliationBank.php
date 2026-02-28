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
        'kase_talep_edildi',
    ];

    protected $casts = [
        'kase_talep_edildi' => 'boolean',
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

    /**
     * Model boot method - event listener'ları kaydet
     */
    protected static function booted()
    {
        // Banka durumu güncellendiğinde request status'ünü güncelle
        static::updated(function ($bank) {
            if ($bank->isDirty('reply_status')) {
                $bank->updateRequestStatus();
            }
        });
    }

    /**
     * Request status'ünü banka durumlarına göre güncelle
     */
    public function updateRequestStatus(): void
    {
        $request = $this->request;
        if (!$request) {
            return;
        }

        $totalBanks = $request->banks()->count();
        if ($totalBanks === 0) {
            return;
        }

        $pendingBanks = $request->banks()->where('reply_status', 'pending')->count();
        $receivedBanks = $request->banks()->whereIn('reply_status', ['received', 'completed'])->count();
        $completedBanks = $request->banks()->where('reply_status', 'completed')->count();

        // Tüm bankalardan cevap geldi
        if ($receivedBanks === $totalBanks && $totalBanks > 0) {
            $newStatus = 'received';
            $updateData = [
                'status' => $newStatus,
            ];
            
            // received_at henüz set edilmediyse set et
            if (!$request->received_at) {
                $updateData['received_at'] = now();
            }
            
            if ($request->status !== $newStatus) {
                $request->update($updateData);
            }
        }
        // Bazı bankalardan cevap geldi (kısmi)
        elseif ($receivedBanks > 0 && $pendingBanks > 0) {
            $newStatus = 'partially';
            if ($request->status !== $newStatus) {
                $request->update(['status' => $newStatus]);
            }
        }
        // Henüz hiç cevap gelmedi ama mail gönderildi
        elseif ($request->status === 'mail_sent' && $pendingBanks === $totalBanks) {
            // Durum değişmedi, mail_sent kalacak
            return;
        }
        // Henüz mail gönderilmedi
        elseif ($request->status === 'pending') {
            // Durum değişmedi, pending kalacak
            return;
        }
    }
}
