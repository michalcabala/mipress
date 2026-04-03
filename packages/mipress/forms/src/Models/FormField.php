<?php

declare(strict_types=1);

namespace MiPress\Forms\Models;

class FormField
{
    public const TYPE_TEXT = 'text';

    public const TYPE_EMAIL = 'email';

    public const TYPE_PHONE = 'phone';

    public const TYPE_TEXTAREA = 'textarea';

    public const TYPE_SELECT = 'select';

    public const TYPE_CHECKBOX = 'checkbox';

    public const TYPE_RADIO = 'radio';

    public const TYPE_FILE = 'file';

    public const TYPE_HIDDEN = 'hidden';

    public const SPAM_HONEYPOT = 'honeypot';

    public const SPAM_RECAPTCHA = 'recaptcha';

    public const SPAM_BOTH = 'both';

    /**
     * @return array<string, string>
     */
    public static function supportedTypes(): array
    {
        return [
            self::TYPE_TEXT => 'Text',
            self::TYPE_EMAIL => 'Email',
            self::TYPE_PHONE => 'Telefon',
            self::TYPE_TEXTAREA => 'Text area',
            self::TYPE_SELECT => 'Select',
            self::TYPE_CHECKBOX => 'Checkbox',
            self::TYPE_RADIO => 'Radio',
            self::TYPE_FILE => 'Soubor',
            self::TYPE_HIDDEN => 'Hidden',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function spamModes(): array
    {
        return [
            self::SPAM_HONEYPOT => 'Honeypot',
            self::SPAM_RECAPTCHA => 'reCAPTCHA v3',
            self::SPAM_BOTH => 'Honeypot + reCAPTCHA v3',
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function contactTemplate(): array
    {
        return [
            [
                'handle' => 'name',
                'type' => self::TYPE_TEXT,
                'label' => 'Jmeno a prijmeni',
                'required' => true,
                'config' => [
                    'placeholder' => 'Jan Novak',
                ],
                'order' => 1,
            ],
            [
                'handle' => 'email',
                'type' => self::TYPE_EMAIL,
                'label' => 'Email',
                'required' => true,
                'config' => [],
                'order' => 2,
            ],
            [
                'handle' => 'phone',
                'type' => self::TYPE_PHONE,
                'label' => 'Telefon',
                'required' => false,
                'config' => [],
                'order' => 3,
            ],
            [
                'handle' => 'message',
                'type' => self::TYPE_TEXTAREA,
                'label' => 'Zprava',
                'required' => true,
                'config' => [
                    'rows' => 5,
                ],
                'order' => 4,
            ],
        ];
    }
}
