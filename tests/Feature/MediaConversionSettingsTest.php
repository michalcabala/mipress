<?php

declare(strict_types=1);

use App\Models\User;
use Livewire\Livewire;
use MiPress\Core\Database\Seeders\PermissionSeeder;
use MiPress\Core\Enums\UserRole;
use MiPress\Core\Filament\Pages\MediaConversionSettings;
use MiPress\Core\Models\Setting;

describe('media conversion settings admin', function () {
    it('renders the dedicated media conversion settings page', function () {
        $this->seed(PermissionSeeder::class);

        $admin = User::factory()->create();
        $admin->assignRole(UserRole::SuperAdmin->value);
        $this->actingAs($admin);

        Livewire::test(MediaConversionSettings::class)
            ->assertSuccessful()
            ->assertSee('Image konverze')
            ->assertSee('Výstup / Rozměry');
    });

    it('normalizes crop specific settings when conversions are saved', function () {
        $this->seed(PermissionSeeder::class);

        $admin = User::factory()->create();
        $admin->assignRole(UserRole::SuperAdmin->value);
        $this->actingAs($admin);

        Livewire::test(MediaConversionSettings::class)
            ->set('data.conversions', [
                [
                    'name' => 'listing_card',
                    'label' => 'Karta výpisu',
                    'description' => 'Kompaktní výstup pro karty a menší seznamy.',
                    'is_active' => true,
                    'sort_order' => 1,
                    'group' => 'thumbnails',
                    'mode' => 'resize',
                    'width' => 320,
                    'height' => null,
                    'aspect_ratio' => '16:9',
                    'allow_upscale' => false,
                    'supports_focal_point' => true,
                    'supports_manual_crop' => true,
                    'manual_crop_required' => true,
                    'default_crop_strategy' => 'manual',
                    'show_in_editor' => true,
                    'important' => false,
                    'priority' => 'normal',
                    'editor_badge' => 'listing',
                    'editor_help_text' => 'Používat pro kompaktní výpisy.',
                    'usage_context' => 'Výpis článků',
                ],
                [
                    'name' => 'hero_teaser',
                    'label' => 'Hero teaser',
                    'description' => 'Výrazný hero výstup se silnou kontrolou kompozice.',
                    'is_active' => true,
                    'sort_order' => 2,
                    'group' => 'hero',
                    'mode' => 'crop_resize',
                    'width' => 1440,
                    'height' => 900,
                    'aspect_ratio' => '8:5',
                    'allow_upscale' => false,
                    'supports_focal_point' => false,
                    'supports_manual_crop' => true,
                    'manual_crop_required' => true,
                    'default_crop_strategy' => 'manual',
                    'show_in_editor' => true,
                    'important' => true,
                    'priority' => 'high',
                    'editor_badge' => 'hero',
                    'editor_help_text' => 'Zkontrolujte kompozici hlavního záběru.',
                    'usage_context' => 'Hero banner',
                ],
            ])
            ->call('save')
            ->assertHasNoErrors();

        $setting = Setting::query()->where('handle', 'media_conversions')->first();

        expect($setting)->not->toBeNull();

        $conversions = collect(data_get($setting?->data, 'conversions', []))->keyBy('name');

        expect($conversions)->toHaveCount(2)
            ->and($conversions['listing_card']['mode'])->toBe('resize')
            ->and($conversions['listing_card']['supports_focal_point'])->toBeFalse()
            ->and($conversions['listing_card']['supports_manual_crop'])->toBeFalse()
            ->and($conversions['listing_card']['manual_crop_required'])->toBeFalse()
            ->and($conversions['listing_card']['default_crop_strategy'])->toBe('none')
            ->and($conversions['hero_teaser']['mode'])->toBe('crop_resize')
            ->and($conversions['hero_teaser']['aspect_ratio'])->toBe('8:5')
            ->and($conversions['hero_teaser']['supports_focal_point'])->toBeFalse()
            ->and($conversions['hero_teaser']['supports_manual_crop'])->toBeTrue()
            ->and($conversions['hero_teaser']['manual_crop_required'])->toBeTrue()
            ->and($conversions['hero_teaser']['default_crop_strategy'])->toBe('manual');
    });
});
