<?php

declare(strict_types=1);

use MiPress\Core\Services\BlueprintFieldResolver;

describe('blueprint field resolver visibility conditions', function () {
    it('shows field when no conditions are configured', function () {
        $fieldDefinition = [
            'handle' => 'subtitle',
            'config' => [],
        ];

        expect(BlueprintFieldResolver::shouldDisplayField($fieldDefinition, []))->toBeTrue();
    });

    it('evaluates all-mode conditions', function () {
        $fieldDefinition = [
            'handle' => 'subtitle',
            'config' => [
                'visibility_mode' => 'all',
                'visibility_conditions' => [
                    ['field' => 'show_subtitle', 'operator' => 'equals', 'value' => '1'],
                    ['field' => 'title', 'operator' => 'filled'],
                    ['field' => 'category', 'operator' => 'not_equals', 'value' => 'archived'],
                ],
            ],
        ];

        expect(BlueprintFieldResolver::shouldDisplayField($fieldDefinition, [
            'show_subtitle' => true,
            'title' => 'Aktualita',
            'category' => 'news',
        ]))->toBeTrue()
            ->and(BlueprintFieldResolver::shouldDisplayField($fieldDefinition, [
                'show_subtitle' => true,
                'title' => 'Aktualita',
                'category' => 'archived',
            ]))->toBeFalse();
    });

    it('evaluates any-mode conditions', function () {
        $fieldDefinition = [
            'handle' => 'highlight',
            'config' => [
                'visibility_mode' => 'any',
                'visibility_conditions' => [
                    ['field' => 'status', 'operator' => 'equals', 'value' => 'published'],
                    ['field' => 'status', 'operator' => 'equals', 'value' => 'scheduled'],
                ],
            ],
        ];

        expect(BlueprintFieldResolver::shouldDisplayField($fieldDefinition, [
            'status' => 'scheduled',
        ]))->toBeTrue()
            ->and(BlueprintFieldResolver::shouldDisplayField($fieldDefinition, [
                'status' => 'draft',
            ]))->toBeFalse();
    });

    it('supports contains operator for arrays and dot paths', function () {
        $fieldDefinition = [
            'handle' => 'cta',
            'config' => [
                'visibility_mode' => 'all',
                'visibility_conditions' => [
                    ['field' => 'tags', 'operator' => 'contains', 'value' => 'featured'],
                    ['field' => 'meta.visibility', 'operator' => 'equals', 'value' => 'public'],
                ],
            ],
        ];

        expect(BlueprintFieldResolver::shouldDisplayField($fieldDefinition, [
            'tags' => ['news', 'featured'],
            'meta' => ['visibility' => 'public'],
        ]))->toBeTrue()
            ->and(BlueprintFieldResolver::shouldDisplayField($fieldDefinition, [
                'tags' => ['news'],
                'meta' => ['visibility' => 'public'],
            ]))->toBeFalse();
    });
});
