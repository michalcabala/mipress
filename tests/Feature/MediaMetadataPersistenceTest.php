<?php

declare(strict_types=1);

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use MiPress\Core\Media\MediaConfig;
use MiPress\Core\Models\Attachment;
use MiPress\Core\Models\Media;

it('persists alt and title without double decoding custom properties', function () {
    Storage::fake(MediaConfig::disk());

    $attachment = Attachment::query()->create([
        'name' => 'Testovaci medium',
    ]);

    /** @var Media $media */
    $media = $attachment
        ->addMedia(UploadedFile::fake()->image('test-image.jpg', 1200, 800))
        ->usingName('test-image')
        ->toMediaCollection(MediaConfig::libraryCollection(), MediaConfig::disk());

    $media->alt = 'Ukazkovy alternativni text';
    $media->title = 'Ukazkovy titulek';
    $media->save();

    $media->refresh();

    expect($media->alt)->toBe('Ukazkovy alternativni text')
        ->and($media->title)->toBe('Ukazkovy titulek')
        ->and($media->custom_properties)->toMatchArray([
            'alt' => 'Ukazkovy alternativni text',
            'title' => 'Ukazkovy titulek',
        ]);
});
