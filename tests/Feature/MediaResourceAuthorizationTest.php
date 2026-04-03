<?php

declare(strict_types=1);

use App\Models\User;
use Awcodes\Curator\Models\Media;
use Awcodes\Curator\Resources\Media\MediaResource;
use MiPress\Core\Database\Seeders\PermissionSeeder;
use MiPress\Core\Enums\UserRole;

beforeEach(function () {
    $this->seed(PermissionSeeder::class);

    $this->media = Media::factory()->create([
        'type' => 'image/jpeg',
        'ext' => 'jpg',
    ]);
});

describe('media curation regeneration permissions', function () {
    it('applies regenerate abilities by role', function (UserRole $role, bool $allowed) {
        $user = User::factory()->create();
        $user->assignRole($role->value);

        expect($user->can('regenerateAllCurations', Media::class))->toBe($allowed)
            ->and($user->can('regenerateSelectedCurations', Media::class))->toBe($allowed)
            ->and($user->can('regenerateSingleCuration', $this->media))->toBe($allowed);
    })->with([
        [UserRole::SuperAdmin, true],
        [UserRole::Admin, true],
        [UserRole::Editor, true],
        [UserRole::Contributor, false],
    ]);
});

describe('media resource action visibility', function () {
    it('shows regenerate all action on index by role', function (UserRole $role, bool $visible) {
        $user = User::factory()->create();
        $user->assignRole($role->value);

        $this->actingAs($user);

        $response = $this->get(MediaResource::getUrl('index'));
        $response->assertSuccessful();

        if ($visible) {
            $response->assertSee('Přegenerovat vše');

            return;
        }

        $response->assertDontSee('Přegenerovat vše');
    })->with([
        [UserRole::SuperAdmin, true],
        [UserRole::Admin, true],
        [UserRole::Editor, true],
        [UserRole::Contributor, false],
    ]);

    it('shows single regenerate action on edit by role', function (UserRole $role, bool $visible) {
        $user = User::factory()->create();
        $user->assignRole($role->value);

        $media = $this->media;

        if ($role === UserRole::Contributor) {
            $media = Media::factory()->create([
                'type' => 'image/jpeg',
                'ext' => 'jpg',
                'uploaded_by' => $user->id,
            ]);
        }

        $this->actingAs($user);

        $response = $this->get(MediaResource::getUrl('edit', ['record' => $media]));
        $response->assertSuccessful();

        if ($visible) {
            $response->assertSee('Přegenerovat ořezy');

            return;
        }

        $response->assertDontSee('Přegenerovat ořezy');
    })->with([
        [UserRole::SuperAdmin, true],
        [UserRole::Admin, true],
        [UserRole::Editor, true],
        [UserRole::Contributor, false],
    ]);
});
