<?php

namespace MiPress\SocialFeeds\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Crypt;
use MiPress\SocialFeeds\Enums\SocialPlatform;

class SocialAccount extends Model
{
    protected $fillable = [
        'platform',
        'platform_account_id',
        'name',
        'username',
        'access_token',
        'refresh_token',
        'token_expires_at',
        'avatar_url',
        'meta',
        'errors',
        'connected_by',
        'last_verified_at',
    ];

    protected function casts(): array
    {
        return [
            'platform' => SocialPlatform::class,
            'token_expires_at' => 'datetime',
            'last_verified_at' => 'datetime',
            'meta' => 'array',
            'errors' => 'array',
        ];
    }

    // ── Accessors (šifrování tokenů) ──

    public function getDecryptedTokenAttribute(): ?string
    {
        if (! $this->access_token) {
            return null;
        }

        try {
            return Crypt::decryptString($this->access_token);
        } catch (\Exception) {
            return null;
        }
    }

    public function getDecryptedRefreshTokenAttribute(): ?string
    {
        if (! $this->refresh_token) {
            return null;
        }

        try {
            return Crypt::decryptString($this->refresh_token);
        } catch (\Exception) {
            return null;
        }
    }

    // ── Mutators ──

    public function setAccessTokenAttribute(string $value): void
    {
        $this->attributes['access_token'] = $this->isEncrypted($value)
            ? $value
            : Crypt::encryptString($value);
    }

    public function setRefreshTokenAttribute(?string $value): void
    {
        $this->attributes['refresh_token'] = $value
            ? ($this->isEncrypted($value) ? $value : Crypt::encryptString($value))
            : null;
    }

    // ── Helpers ──

    public function isTokenExpired(): bool
    {
        if (! $this->token_expires_at) {
            return false;
        }

        return $this->token_expires_at->isPast();
    }

    public function isTokenExpiringSoon(int $thresholdDays = 7): bool
    {
        if (! $this->token_expires_at) {
            return false;
        }

        return $this->token_expires_at->isBefore(now()->addDays($thresholdDays));
    }

    public function recordError(string $message, ?string $code = null): void
    {
        $errors = $this->errors ?? [];
        $errors[] = [
            'message' => $message,
            'code' => $code,
            'occurred_at' => now()->toIso8601String(),
        ];

        $this->update(['errors' => array_slice($errors, -10)]);
    }

    public function clearErrors(): void
    {
        $this->update(['errors' => null]);
    }

    private function isEncrypted(string $value): bool
    {
        try {
            Crypt::decryptString($value);

            return true;
        } catch (\Exception) {
            return false;
        }
    }

    // ── Relationships ──

    public function feeds(): HasMany
    {
        return $this->hasMany(SocialFeed::class);
    }

    public function connectedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'connected_by');
    }
}
