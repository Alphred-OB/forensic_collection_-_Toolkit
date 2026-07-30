<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CaseAssignment extends Model
{
    protected $fillable = [
        'case_id',
        'user_id',
        'role_in_case',
        'assigned_at',
    ];

    public function case()
    {
        return $this->belongsTo(ForensicCase::class, 'case_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
