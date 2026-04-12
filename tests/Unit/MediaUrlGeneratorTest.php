<?php

declare(strict_types=1);

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use MiPress\Core\Media\MediaConfig;
use MiPress\Core\Models\Attachment;
use MiPress\Core\Models\Media;
use MiPress\Core\Services\MediaUrlGenerator;

beforeEach(function () {
    Storage::fake(MediaConfig::disk());
});

describe('media url generator', function () {
    it('uses a generated conversion url when the requested variant exists', function () {
        $attachment = Attachment::query()->create([
            'name' => 'Článek',
        ]);

        /** @var Media $media */
        $media = $attachment
            ->addMedia(UploadedFile::fake()->image('article.jpg', 1600, 900))
            ->usingName('article')
            ->toMediaCollection(MediaConfig::libraryCollection(), MediaConfig::disk());

        $media->generated_conversions = ['thumbnail' => true];
        $media->saveQuietly();

        $url = app(MediaUrlGenerator::class)->media($media, 'thumbnail');

        expect($url)->toContain('article-thumbnail.webp')
            ->and($url)->toContain('storage')
            ->and(filter_var($url, FILTER_VALIDATE_URL))->not->toBeFalse();
    });

    it('builds storage urls from local uploads paths', function () {
        Storage::disk(MediaConfig::disk())->put('avatars/users/avatar.webp', 'avatar');

        $url = app(MediaUrlGenerator::class)->path('avatars/users/avatar.webp', 'avatar', MediaConfig::disk());

        expect($url)->toContain('avatars/users/avatar.webp')
            ->and($url)->toContain('/storage/')
            ->and(filter_var($url, FILTER_VALIDATE_URL))->not->toBeFalse();
    });
});
