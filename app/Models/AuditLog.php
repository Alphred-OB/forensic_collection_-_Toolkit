<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'action_type',
        'target_type',
        'target_id',
        'details_json',
        'entry_hash',
        'previous_entry_hash',
        'created_at',
    ];

    protected $casts = [
        'details_json' => 'array',
        'created_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
