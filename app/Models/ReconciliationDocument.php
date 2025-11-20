<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReconciliationDocument extends Model
{
    protected $fillable = [
        'bank_id',
        'file_path',
        'file_name',
        'file_type',
        'uploaded_by',
        'uploaded_at',
        'notes',
    ];

    protected $casts = [
        'uploaded_at' => 'datetime',
    ];

    public function bank()
    {
        return $this->belongsTo(ReconciliationBank::class, 'bank_id');
    }

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
