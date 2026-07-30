<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ForensicCase extends Model
{
    use SoftDeletes;

    protected $table = 'cases';

    protected $fillable = [
        'case_number',
        'title',
        'description',
        'status',
        'priority',
        'tags',
        'created_by',
        'closed_at',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function assignments()
    {
        return $this->hasMany(CaseAssignment::class, 'case_id');
    }

    public function assignedUsers()
    {
        return $this->belongsToMany(User::class, 'case_assignments', 'case_id', 'user_id');
    }

    public function evidenceItems()
    {
        return $this->hasMany(EvidenceItem::class, 'case_id');
    }

    public function reports()
    {
        return $this->hasMany(Report::class, 'case_id');
    }

    public function scopeSearch($query, $search)
    {
        if ($search) {
            return $query->where(function ($q) use ($search) {
                $q->where('case_number', 'like', "%{$search}%")
                  ->orWhere('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }
        return $query;
    }

    public function scopeStatus($query, $status)
    {
        if ($status && in_array($status, ['open', 'closed', 'archived'])) {
            return $query->where('status', $status);
        }
        return $query;
    }

    public function isEditable(): bool
    {
        return $this->status === 'open';
    }
}
