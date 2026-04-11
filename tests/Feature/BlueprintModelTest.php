<?php

declare(strict_types=1);

use Illuminate\Validation\ValidationException;
use MiPress\Core\Models\Blueprint;

it('normalizes nested blueprint fields before saving', function () {
    $blueprint = Blueprint::factory()->create([
        'fields' => [
            [
                'section' => ' ',
                'fields' => [
                    [
                        'handle' => ' title ',
                        'label' => ' ',
                        'type' => 'text',
                        'required' => '1',
                        'show_in_table' => 'yes',
                        'searchable' => 'on',
                        'sortable' => '0',
                        'order' => '7',
                        'config' => 'invalid',
                    ],
                    [
                        'handle' => ' ',
                        'type' => 'text',
                    ],
                ],
            ],
        ],
    ]);

    $fields = $blueprint->fresh()->fields;

    expect($fields)->toHaveCount(1)
        ->and($fields[0]['section'])->toBe('Sekce 1')
        ->and($fields[0]['fields'])->toHaveCount(1)
        ->and($fields[0]['fields'][0]['handle'])->toBe('title')
        ->and($fields[0]['fields'][0]['label'])->toBe('title')
        ->and($fields[0]['fields'][0]['required'])->toBeTrue()
        ->and($fields[0]['fields'][0]['show_in_table'])->toBeTrue()
        ->and($fields[0]['fields'][0]['searchable'])->toBeTrue()
        ->and($fields[0]['fields'][0]['sortable'])->toBeFalse()
        ->and($fields[0]['fields'][0]['order'])->toBe(7)
        ->and($fields[0]['fields'][0]['config'])->toBe([]);
});

it('normalizes flat blueprint field payloads before saving', function () {
    $blueprint = Blueprint::factory()->create([
        'fields' => [
            [
                'handle' => ' summary ',
                'label' => 'Summary',
                'type' => 'textarea',
                'required' => 'false',
                'order' => '2',
                'config' => ['rows' => 3],
            ],
            [
                'handle' => '',
                'type' => 'text',
            ],
        ],
    ]);

    $fields = $blueprint->fresh()->fields;

    expect($fields)->toHaveCount(1)
        ->and($fields[0]['handle'])->toBe('summary')
        ->and($fields[0]['required'])->toBeFalse()
        ->and($fields[0]['order'])->toBe(2)
        ->and($fields[0]['config'])->toBe(['rows' => 3]);
});

it('rejects duplicate field handles in one blueprint', function () {
    expect(fn () => Blueprint::factory()->create([
        'fields' => [
            [
                'section' => 'Main',
                'fields' => [
                    ['handle' => 'title', 'type' => 'text'],
                ],
            ],
            [
                'section' => 'Seo',
                'fields' => [
                    ['handle' => 'Title', 'type' => 'text'],
                ],
            ],
        ],
    ]))
        ->toThrow(ValidationException::class, 'Handle pole musi byt');
});

it('rejects unknown field types in one blueprint', function () {
    expect(fn () => Blueprint::factory()->create([
        'fields' => [
            [
                'handle' => 'intro',
                'type' => 'unknown-type',
            ],
        ],
    ]))
        ->toThrow(ValidationException::class, 'Nalezene neplatne typy poli');
});
