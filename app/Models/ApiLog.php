<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApiLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'method',
        'endpoint',
        'ip_address',
        'user_agent',
        'request_headers',
        'request_body',
        'response_headers',
        'response_body',
        'response_status',
        'response_time',
        'session_id',
        'request_id',
        'error_type',
        'error_message',
        'logged_at',
    ];

    protected $casts = [
        'request_headers' => 'array',
        'request_body' => 'array',
        'response_headers' => 'array',
        'response_body' => 'array',
        'response_time' => 'float',
        'logged_at' => 'datetime',
    ];

    /**
     * Get the user that made the API request
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope for filtering by date range
     */
    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('logged_at', [$startDate, $endDate]);
    }

    /**
     * Scope for filtering by user
     */
    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope for filtering by endpoint
     */
    public function scopeByEndpoint($query, $pattern)
    {
        return $query->where('endpoint', 'LIKE', "%{$pattern}%");
    }

    /**
     * Scope for filtering by method
     */
    public function scopeByMethod($query, $method)
    {
        return $query->where('method', $method);
    }

    /**
     * Scope for filtering by status code
     */
    public function scopeByStatus($query, $status)
    {
        return $query->where('response_status', $status);
    }

    /**
     * Scope for filtering by IP address
     */
    public function scopeByIp($query, $ip)
    {
        return $query->where('ip_address', 'LIKE', "%{$ip}%");
    }

    /**
     * Scope for errors only
     */
    public function scopeErrorsOnly($query)
    {
        return $query->where('response_status', '>=', 400);
    }

    /**
     * Get formatted response time
     */
    public function getFormattedResponseTimeAttribute(): string
    {
        if ($this->response_time < 1000) {
            return number_format($this->response_time, 2) . ' ms';
        }
        
        return number_format($this->response_time / 1000, 2) . ' s';
    }

    /**
     * Get status color for display
     */
    public function getStatusColorAttribute(): string
    {
        if ($this->response_status >= 200 && $this->response_status < 300) {
            return 'success';
        } elseif ($this->response_status >= 300 && $this->response_status < 400) {
            return 'warning';
        } elseif ($this->response_status >= 400) {
            return 'danger';
        }
        
        return 'primary';
    }
}