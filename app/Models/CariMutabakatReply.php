<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CariMutabakatReply extends Model
{
    protected $table = 'cari_mutabakat_replies';

    protected $fillable = [
        'item_id',
        'cevap',
        'cevaplayan_unvan',
        'cevaplayan_vergi_no',
        'aciklama',
        'ekstre_path',
        'e_imzali_form_path',
        'pdf_path',
        'replied_at',
    ];

    protected $casts = [
        'replied_at' => 'datetime',
    ];

    public function item()
    {
        return $this->belongsTo(CariMutabakatItem::class);
    }
}
