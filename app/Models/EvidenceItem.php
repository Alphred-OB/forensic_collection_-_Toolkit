<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class EvidenceItem extends Model
{
    protected $fillable = [
        'case_id',
        'parent_id',
        'evidence_number',
        'description',
        'source_device',
        'classification',
        'custom_classification',
        'file_path',
        'file_name',
        'file_hash_sha256',
        'file_size',
        'uploaded_by',
        'collected_at',
        'collected_location',
        'current_custodian_id',
    ];

    public function parent()
    {
        return $this->belongsTo(EvidenceItem::class, 'parent_id');
    }

    public function subItems()
    {
        return $this->hasMany(EvidenceItem::class, 'parent_id');
    }

    protected $casts = [
        'collected_at' => 'datetime',
    ];

    public function case()
    {
        return $this->belongsTo(ForensicCase::class, 'case_id');
    }

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function currentCustodian()
    {
        return $this->belongsTo(User::class, 'current_custodian_id');
    }

    public function transfers()
    {
        return $this->hasMany(CustodyTransfer::class, 'evidence_item_id');
    }

    /**
     * Re-verify stored file integrity against saved SHA-256 hash.
     */
    public function verifyIntegrity(): bool
    {
        if (!Storage::disk('local')->exists($this->file_path)) {
            return false;
        }

        $currentHash = hash_file('sha256', Storage::disk('local')->path($this->file_path));
        return hash_equals($this->file_hash_sha256, $currentHash);
    }
}
