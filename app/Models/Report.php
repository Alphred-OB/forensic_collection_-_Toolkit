<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Report extends Model
{
    protected $fillable = [
        'case_id',
        'evidence_item_id',
        'type',
        'file_path',
        'generated_by',
        'generated_at',
        'manifest_hash',
    ];

    protected $casts = [
        'generated_at' => 'datetime',
    ];

    public function case()
    {
        return $this->belongsTo(ForensicCase::class, 'case_id');
    }

    public function evidenceItem()
    {
        return $this->belongsTo(EvidenceItem::class);
    }

    public function generator()
    {
        return $this->belongsTo(User::class, 'generated_by');
    }
}
