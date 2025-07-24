<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

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
        'last_heartbeat_at',
        'session_status',
        'device_info',
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
            'last_heartbeat_at' => 'datetime',
            'account_is_set' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    /**
     * User type constants
     */
    const USER_TYPES = [
        'admin' => 'Administrator',
        'manager' => 'Manager',
        'technician' => 'Technician',
        'analyst' => 'Analyst',
    ];

    /**
     * Session status constants
     */
    const SESSION_STATUSES = [
        'online' => 'Online',
        'offline' => 'Offline', 
        'away' => 'Away',
    ];

    /**
     * Get user's full name
     */
    public function getFullNameAttribute(): string
    {
        $name = trim($this->first_name . ' ' . $this->last_name);
        return $name ?: $this->email;
    }

    /**
     * Get user's name for Filament
     */
    public function getNameAttribute(): string
    {
        return $this->full_name;
    }

    /**
     * Get user type label
     */
    public function getUserTypeLabel(): string
    {
        return self::USER_TYPES[$this->user_type] ?? 'Unknown';
    }

    /**
     * Update last login timestamp
     */
    public function updateLastLogin(): void
    {
        $this->update(['last_login_at' => now()]);
    }

    /**
     * Update heartbeat timestamp and set user as online
     */
    public function updateHeartbeat(string $deviceInfo = null): void
    {
        $this->update([
            'last_heartbeat_at' => now(),
            'session_status' => 'online',
            'device_info' => $deviceInfo ?? $this->device_info,
        ]);
    }

    /**
     * Set user as offline
     */
    public function setOffline(): void
    {
        $this->update([
            'session_status' => 'offline',
        ]);
    }

    /**
     * Set user as away (when heartbeat is stale but not completely offline)
     */
    public function setAway(): void
    {
        $this->update([
            'session_status' => 'away',
        ]);
    }

    /**
     * Check if user is currently online (heartbeat within the last 2 minutes)
     */
    public function isOnline(): bool
    {
        if (!$this->last_heartbeat_at) {
            return false;
        }
        
        return $this->last_heartbeat_at->gt(now()->subMinutes(2));
    }

    /**
     * Check if user is away (heartbeat between 2-10 minutes ago)
     */
    public function isAway(): bool
    {
        if (!$this->last_heartbeat_at) {
            return false;
        }
        
        return $this->last_heartbeat_at->between(
            now()->subMinutes(10),
            now()->subMinutes(2)
        );
    }

    /**
     * Check if user should be considered offline
     */
    public function shouldBeOffline(): bool
    {
        if (!$this->last_heartbeat_at) {
            return true;
        }
        
        return $this->last_heartbeat_at->lt(now()->subMinutes(10));
    }

    /**
     * Get session status label
     */
    public function getSessionStatusLabel(): string
    {
        return self::SESSION_STATUSES[$this->session_status] ?? 'Unknown';
    }

    /**
     * Scope for online users
     */
    public function scopeOnline($query)
    {
        return $query->where('session_status', 'online')
                     ->where('last_heartbeat_at', '>', now()->subMinutes(2));
    }

    /**
     * Scope for offline users
     */
    public function scopeOffline($query)
    {
        return $query->where('session_status', 'offline')
                     ->orWhere('last_heartbeat_at', '<', now()->subMinutes(10))
                     ->orWhereNull('last_heartbeat_at');
    }

    /**
     * Scope for away users
     */
    public function scopeAway($query)
    {
        return $query->where('session_status', 'away')
                     ->whereBetween('last_heartbeat_at', [
                         now()->subMinutes(10),
                         now()->subMinutes(2)
                     ]);
    }
}