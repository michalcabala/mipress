<?php

declare(strict_types=1);

namespace MiPress\Forms\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Form extends Model
{
    use HasFactory;

    protected $table = 'forms';

    protected $fillable = [
        'title',
        'handle',
        'description',
        'fields',
        'recipients',
        'auto_reply_enabled',
        'auto_reply_subject',
        'auto_reply_body',
        'success_message',
        'spam_protection',
        'recaptcha_site_key',
        'recaptcha_secret_key',
        'is_active',
    ];

    protected $attributes = [
        'fields' => '[]',
        'recipients' => '[]',
        'auto_reply_enabled' => false,
        'success_message' => 'Dekuujeme, formular byl odeslan.',
        'spam_protection' => 'honeypot',
        'is_active' => true,
    ];

    protected $casts = [
        'fields' => 'array',
        'recipients' => 'array',
        'auto_reply_enabled' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function submissions(): HasMany
    {
        return $this->hasMany(FormSubmission::class);
    }

    public function attachments(): HasManyThrough
    {
        return $this->hasManyThrough(
            FormSubmissionAttachment::class,
            FormSubmission::class,
            'form_id',
            'submission_id',
            'id',
            'id',
        );
    }

    /**
     * @return array<int, int>
     */
    public function recipientIds(): array
    {
        return collect($this->recipients ?? [])
            ->map(static fn (mixed $value): int => (int) $value)
            ->filter(static fn (int $value): bool => $value > 0)
            ->unique()
            ->values()
            ->all();
    }

    public function recipientsQuery()
    {
        return User::query()->whereKey($this->recipientIds());
    }

    public function getRouteKeyName(): string
    {
        return 'handle';
    }
}
