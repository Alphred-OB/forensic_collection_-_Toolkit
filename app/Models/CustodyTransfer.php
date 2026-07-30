<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustodyTransfer extends Model
{
    protected $fillable = [
        'evidence_item_id',
        'from_user_id',
        'to_user_id',
        'reason',
        'transferred_at',
        'accepted_at',
        'status',
    ];

    protected $casts = [
        'transferred_at' => 'datetime',
        'accepted_at' => 'datetime',
    ];

    public function evidenceItem()
    {
        return $this->belongsTo(EvidenceItem::class);
    }

    public function fromUser()
    {
        return $this->belongsTo(User::class, 'from_user_id');
    }

    public function toUser()
    {
        return $this->belongsTo(User::class, 'to_user_id');
    }
}
