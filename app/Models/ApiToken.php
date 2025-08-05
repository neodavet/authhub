<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;
use Carbon\Carbon;

class ApiToken extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'token',
        'abilities',
        'application_id',
        'user_id',
        'expires_at',
        'last_used_at',
        'is_active',
        'created_from_ip',
        'user_agent',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'abilities' => 'array',
        'expires_at' => 'datetime',
        'last_used_at' => 'datetime',
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'token',
    ];

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($apiToken) {
            if (empty($apiToken->token)) {
                $apiToken->token = hash('sha256', $plainTextToken = Str::random(64));
            }
            if (empty($apiToken->abilities)) {
                $apiToken->abilities = ['read'];
            }
        });
    }

    /**
     * Get the user that owns the token.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the application that owns the token.
     */
    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }

    /**
     * Scope a query to only include active tokens.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to only include non-expired tokens.
     */
    public function scopeNotExpired($query)
    {
        return $query->where(function ($query) {
            $query->whereNull('expires_at')
                  ->orWhere('expires_at', '>', now());
        });
    }

    /**
     * Check if the token is expired.
     */
    public function isExpired(): bool
    {
        if (is_null($this->expires_at)) {
            return false;
        }

        return $this->expires_at->isPast();
    }

    /**
     * Check if the token can perform the given ability.
     */
    public function can(string $ability): bool
    {
        return in_array('*', $this->abilities) || in_array($ability, $this->abilities);
    }

    /**
     * Update the last used timestamp.
     */
    public function markAsUsed(): void
    {
        $this->update(['last_used_at' => now()]);
    }

    /**
     * Revoke the token.
     */
    public function revoke(): bool
    {
        return $this->update(['is_active' => false]);
    }
}
