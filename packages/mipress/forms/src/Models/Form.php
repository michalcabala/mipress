<?php

declare(strict_types=1);

namespace MiPress\Forms\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

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

    public function getRouteKeyName(): string
    {
        return 'handle';
    }
}
