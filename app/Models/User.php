<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\UserFactory;
use Filament\Auth\MultiFactor\Email\Concerns\InteractsWithEmailAuthentication;
use Filament\Auth\MultiFactor\Email\Contracts\HasEmailAuthentication;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasAvatar;
use Illuminate\Auth\MustVerifyEmail as MustVerifyEmailTrait;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;
use MiPress\Core\Models\Media;
use MiPress\Core\Traits\HasRoles;

#[Fillable(['name', 'email', 'password', 'avatar_path'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements FilamentUser, HasAvatar, HasEmailAuthentication, MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory;

    use HasRoles;
    use InteractsWithEmailAuthentication;
    use MustVerifyEmailTrait;
    use Notifiable;

    protected static function booted(): void
    {
        static::updating(function (self $user): void {
            if (! $user->isDirty('avatar_path')) {
                return;
            }

            static::deleteAvatarFile($user->getOriginal('avatar_path'));
        });

        static::forceDeleted(function (self $user): void {
            static::deleteAvatarFile($user->avatar_path);
        });
    }

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

    public function avatar(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'avatar_id');
    }

    public function getFilamentAvatarUrl(): ?string
    {
        if (filled($this->avatar_path)) {
            return url(Storage::disk('public')->url($this->avatar_path));
        }

        return $this->avatar instanceof Media
            ? mipress_media_url($this->avatar, 'avatar')
            : null;
    }

    private static function deleteAvatarFile(mixed $path): void
    {
        if (! is_string($path) || trim($path) === '') {
            return;
        }

        Storage::disk('public')->delete($path);
    }
}
