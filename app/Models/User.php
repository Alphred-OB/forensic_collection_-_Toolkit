<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'two_factor_secret',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Relationship: Cases created by this user.
     */
    public function createdCases()
    {
        return $this->hasMany(ForensicCase::class, 'created_by');
    }

    /**
     * Relationship: Cases assigned to this user.
     */
    public function assignedCases()
    {
        return $this->belongsToMany(ForensicCase::class, 'case_assignments', 'user_id', 'case_id');
    }

    /**
     * Relationship: Evidence items currently under this user's custody.
     */
    public function custodyItems()
    {
        return $this->hasMany(EvidenceItem::class, 'current_custodian_id');
    }
}
