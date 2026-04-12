<?php

declare(strict_types=1);

use App\Filament\Resources\MediaResource;
use App\Filament\Resources\MediaResource\Pages\EditMedia;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use MiPress\Core\Database\Seeders\PermissionSeeder;
use MiPress\Core\Jobs\RegenerateMediaConversionsJob;
use MiPress\Core\Media\MediaConfig;
use MiPress\Core\Models\Attachment;
use MiPress\Core\Models\Media;

beforeEach(function () {
    $this->seed(PermissionSeeder::class);
    Storage::fake(MediaConfig::disk());
});

function createEditableMedia(): Media
{
    $attachment = Attachment::query()->create([
        'name' => 'Editor medium',
    ]);

    /** @var Media $media */
    $media = $attachment
        ->addMedia(UploadedFile::fake()->image('editor-image.jpg', 1200, 800))
        ->usingName('editor-image')
        ->toMediaCollection(MediaConfig::libraryCollection(), MediaConfig::disk());

    return $media->refresh();
}

function createMediaAdmin(): User
{
    $user = User::factory()->create();
    $user->assignRole('admin');

    return $user;
}

describe('media editor actions', function () {
    it('saves focal point through the form (inline tab)', function () {
        Bus::fake();

        $user = createMediaAdmin();
        $media = createEditableMedia();

        $this->actingAs($user);

        Livewire::test(EditMedia::class, ['record' => $media->getRouteKey()])
            ->fillForm([
                'focal_point_x' => 62,
                'focal_point_y' => 28,
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $media->refresh();

        expect((int) $media->focal_point_x)->toBe(62)
            ->and((int) $media->focal_point_y)->toBe(28);

        Bus::assertDispatched(RegenerateMediaConversionsJob::class);
    });

    it('renders crop actions and tab layout on the edit page', function () {
        $user = createMediaAdmin();
        $media = createEditableMedia();

        $this->actingAs($user)
            ->get(MediaResource::getUrl('edit', ['record' => $media]))
            ->assertSuccessful()
            ->assertSee('Upravit originál')
            ->assertSee('Focal point a konverze');
    });
});
