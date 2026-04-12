<?php

declare(strict_types=1);

use App\Filament\Resources\MediaResource as FilamentMediaResource;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use MiPress\Core\Database\Seeders\PermissionSeeder;
use MiPress\Core\Enums\UserRole;
use MiPress\Core\Media\MediaConfig;
use MiPress\Core\Models\Attachment;
use MiPress\Core\Models\Media;

beforeEach(function () {
    $this->seed(PermissionSeeder::class);
    Storage::fake(MediaConfig::disk());

    $this->media = createLibraryMedia();
});

function createLibraryMedia(?int $uploadedBy = null): Media
{
    $attachment = Attachment::query()->create([
        'name' => 'Testovací médium',
    ]);

    /** @var Media $media */
    $media = $attachment
        ->addMedia(UploadedFile::fake()->image('test-image.jpg', 1200, 800))
        ->usingName('test-image')
        ->toMediaCollection(MediaConfig::libraryCollection(), MediaConfig::disk());

    if ($uploadedBy !== null) {
        $media->forceFill([
            'uploaded_by' => $uploadedBy,
        ])->saveQuietly();
    }

    return $media->refresh();
}

describe('media conversion regeneration permissions', function () {
    it('applies regenerate-all ability by role', function (UserRole $role, bool $allowed) {
        $user = User::factory()->create();
        $user->assignRole($role->value);

        expect($user->can('regenerateAllConversions', Media::class))->toBe($allowed);
    })->with([
        [UserRole::SuperAdmin, true],
        [UserRole::Admin, true],
        [UserRole::Editor, true],
        [UserRole::Contributor, false],
    ]);

    it('allows contributor to regenerate own medium only', function () {
        $user = User::factory()->create();
        $user->assignRole(UserRole::Contributor->value);

        $ownedMedia = createLibraryMedia($user->id);

        expect($user->can('regenerateConversions', $ownedMedia))->toBeTrue()
            ->and($user->can('regenerateConversions', $this->media))->toBeFalse();
    });
});

describe('media resource action visibility', function () {
    it('shows regenerate all action on index by role', function (UserRole $role, bool $visible) {
        $user = User::factory()->create();
        $user->assignRole($role->value);

        $this->actingAs($user);

        $response = $this->get(FilamentMediaResource::getUrl('index'));
        $response->assertSuccessful();

        if ($visible) {
            $response->assertSee('Regenerovat všechny konverze');

            return;
        }

        $response->assertDontSee('Regenerovat všechny konverze');
    })->with([
        [UserRole::SuperAdmin, true],
        [UserRole::Admin, true],
        [UserRole::Editor, true],
        [UserRole::Contributor, false],
    ]);

    it('shows single regenerate action on edit by role', function (UserRole $role, bool $visible) {
        $user = User::factory()->create();
        $user->assignRole($role->value);

        $media = $role === UserRole::Contributor
            ? createLibraryMedia($user->id)
            : $this->media;

        $this->actingAs($user);

        $response = $this->get(FilamentMediaResource::getUrl('edit', ['record' => $media]));
        $response->assertSuccessful();

        if ($visible) {
            $response->assertSee('Regenerovat konverze');

            return;
        }

        $response->assertDontSee('Regenerovat konverze');
    })->with([
        [UserRole::SuperAdmin, true],
        [UserRole::Admin, true],
        [UserRole::Editor, true],
        [UserRole::Contributor, true],
    ]);
});
