<?php

declare(strict_types=1);

use MiPress\Core\Enums\ContentStatus;
use MiPress\Core\Enums\EntryStatus;

it('keeps the legacy entry status alias compatible', function () {
    expect(EntryStatus::Published->value)->toBe(ContentStatus::Published->value)
        ->and(EntryStatus::Published->getLabel())->toBe(ContentStatus::Published->getLabel())
        ->and(EntryStatus::tryFrom(ContentStatus::Draft->value))->toBe(ContentStatus::Draft)
        ->and(EntryStatus::cases())->toHaveCount(count(ContentStatus::cases()));
});
