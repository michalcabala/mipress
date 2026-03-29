<?php

declare(strict_types=1);

use App\Models\User;
use Livewire\Livewire;
use MiPress\Core\Database\Seeders\PermissionSeeder;
use MiPress\Core\Enums\UserRole;
use MiPress\Core\Filament\Resources\BlueprintResource;
use MiPress\Core\Filament\Resources\BlueprintResource\Pages\CreateBlueprint;
use MiPress\Core\Filament\Resources\BlueprintResource\Pages\EditBlueprint;
use MiPress\Core\Filament\Resources\BlueprintResource\Pages\ListBlueprints;
use MiPress\Core\Models\Blueprint;

beforeEach(function () {
    $this->seed(PermissionSeeder::class);

    $this->admin = User::factory()->create();
    $this->admin->assignRole(UserRole::SuperAdmin->value);
    $this->actingAs($this->admin);
});

// --- List Page ---

describe('list page', function () {
    it('can render', function () {
        $this->get(BlueprintResource::getUrl('index'))
            ->assertSuccessful();
    });

    it('can list blueprints', function () {
        $blueprints = Blueprint::factory()->count(3)->create();

        Livewire::test(ListBlueprints::class)
            ->assertCanSeeTableRecords($blueprints);
    });

    it('can search by name', function () {
        $target = Blueprint::factory()->create(['name' => 'Hlavní šablona']);
        $other = Blueprint::factory()->create(['name' => 'Jiná šablona']);

        Livewire::test(ListBlueprints::class)
            ->searchTable('Hlavní')
            ->assertCanSeeTableRecords([$target])
            ->assertCanNotSeeTableRecords([$other]);
    });
});

// --- Create Page ---

describe('create page', function () {
    it('can render', function () {
        $this->get(BlueprintResource::getUrl('create'))
            ->assertSuccessful();
    });

    it('can create a blueprint with basic fields', function () {
        Livewire::test(CreateBlueprint::class)
            ->fillForm([
                'name' => 'Testovací šablona',
                'handle' => 'test_template',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $blueprint = Blueprint::where('handle', 'test_template')->first();

        expect($blueprint)->not->toBeNull()
            ->and($blueprint->name)->toBe('Testovací šablona');
    });

    it('validates required fields', function () {
        Livewire::test(CreateBlueprint::class)
            ->fillForm([
                'name' => null,
                'handle' => null,
            ])
            ->call('create')
            ->assertHasFormErrors([
                'name' => 'required',
                'handle' => 'required',
            ]);
    });

    it('validates unique handle', function () {
        Blueprint::factory()->create(['handle' => 'existing']);

        Livewire::test(CreateBlueprint::class)
            ->fillForm([
                'name' => 'Duplicate',
                'handle' => 'existing',
            ])
            ->call('create')
            ->assertHasFormErrors(['handle' => 'unique']);
    });
});

// --- Edit Page ---

describe('edit page', function () {
    it('can render', function () {
        $blueprint = Blueprint::factory()->create();

        $this->get(BlueprintResource::getUrl('edit', ['record' => $blueprint]))
            ->assertSuccessful();
    });

    it('can fill form with existing data', function () {
        $blueprint = Blueprint::factory()->create([
            'name' => 'Moje Šablona',
            'handle' => 'moje_sablona',
        ]);

        Livewire::test(EditBlueprint::class, ['record' => $blueprint->getRouteKey()])
            ->assertFormSet([
                'name' => 'Moje Šablona',
                'handle' => 'moje_sablona',
            ]);
    });

    it('can update a blueprint', function () {
        $blueprint = Blueprint::factory()->create([
            'name' => 'Original',
            'handle' => 'original',
        ]);

        Livewire::test(EditBlueprint::class, ['record' => $blueprint->getRouteKey()])
            ->fillForm([
                'name' => 'Upravená',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        expect($blueprint->fresh()->name)->toBe('Upravená');
    });
});

// --- Delete ---

describe('delete', function () {
    it('can delete a blueprint', function () {
        $blueprint = Blueprint::factory()->create();

        Livewire::test(EditBlueprint::class, ['record' => $blueprint->getRouteKey()])
            ->callAction('delete');

        expect(Blueprint::find($blueprint->id))->toBeNull();
    });
});

// --- Authorization ---

describe('authorization', function () {
    it('denies access to contributor role', function () {
        $contributor = User::factory()->create();
        $contributor->assignRole(UserRole::Contributor->value);
        $this->actingAs($contributor);

        $this->get(BlueprintResource::getUrl('index'))
            ->assertForbidden();
    });

    it('denies access to editor role', function () {
        $editor = User::factory()->create();
        $editor->assignRole(UserRole::Editor->value);
        $this->actingAs($editor);

        $this->get(BlueprintResource::getUrl('index'))
            ->assertForbidden();
    });
});
