<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasApiTokens;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'phone_number',
        'password',
        'date_hired',
        'user_type',
        'account_is_set',
        'is_active',
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
            'date_hired' => 'date',
            'last_login_at' => 'datetime',
            'account_is_set' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    /**
     * User type constants
     */
    const USER_TYPES = [
        'admin' => 'Administrator',
        'lab_technician' => 'Lab Technician',
        'quality_control' => 'Quality Control',
        'supervisor' => 'Supervisor',
        'researcher' => 'Researcher',
        'manager' => 'Manager',
    ];

    /**
     * Get full name attribute
     */
    public function getFullNameAttribute(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }

    /**
     * Get user type label
     */
    public function getUserTypeLabel(): string
    {
        return self::USER_TYPES[$this->user_type] ?? ucfirst($this->user_type);
    }

    /**
     * Check if user is admin
     */
    public function isAdmin(): bool
    {
        return $this->user_type === 'admin';
    }

    /**
     * Check if user needs to set their account
     */
    public function needsAccountSetup(): bool
    {
        return !$this->account_is_set;
    }

    /**
     * Mark account as set up
     */
    public function markAccountAsSet(): void
    {
        $this->update(['account_is_set' => true]);
    }

    /**
     * Update last login timestamp
     */
    public function updateLastLogin(): void
    {
        $this->update(['last_login_at' => now()]);
    }

    /**
     * Scope for active users
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for users by type
     */
    public function scopeByType($query, $type)
    {
        return $query->where('user_type', $type);
    }
}