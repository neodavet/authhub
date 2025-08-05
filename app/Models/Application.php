<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Application extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'description',
        'client_id',
        'client_secret',
        'callback_urls',
        'allowed_scopes',
        'is_active',
        'user_id',
        'rate_limit',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'callback_urls' => 'array',
        'allowed_scopes' => 'array',
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
        'client_secret',
    ];

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($application) {
            if (empty($application->client_id)) {
                $application->client_id = Str::uuid();
            }
            if (empty($application->client_secret)) {
                $application->client_secret = Str::random(64);
            }
            if (empty($application->allowed_scopes)) {
                $application->allowed_scopes = ['read'];
            }
        });
    }

    /**
     * Get the user that owns the application.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the API tokens for the application.
     */
    public function apiTokens(): HasMany
    {
        return $this->hasMany(ApiToken::class);
    }

    /**
     * Get active API tokens for the application.
     */
    public function activeApiTokens(): HasMany
    {
        return $this->hasMany(ApiToken::class)->where('is_active', true);
    }

    /**
     * Scope a query to only include active applications.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Generate a new client secret.
     */
    public function regenerateClientSecret(): string
    {
        $this->client_secret = Str::random(64);
        $this->save();
        
        return $this->client_secret;
    }
}
