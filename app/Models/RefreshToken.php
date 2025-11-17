<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RefreshToken extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'token',
        'expires_at',
        'is_revoked',
        'revoked_at',
        'user_agent',
        'ip_address',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'is_revoked' => 'boolean',
        'revoked_at' => 'datetime',
    ];

    protected $hidden = [
        'token',
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Scopes
    public function scopeValid($query)
    {
        return $query->where('is_revoked', false)
            ->where('expires_at', '>', now());
    }

    public function scopeExpired($query)
    {
        return $query->where('expires_at', '<=', now());
    }

    // Helper methods
    public function revoke(): void
    {
        $this->update([
            'is_revoked' => true,
            'revoked_at' => now(),
        ]);
    }

    public function isValid(): bool
    {
        return !$this->is_revoked && $this->expires_at > now();
    }
}
