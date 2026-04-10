<?php

declare(strict_types=1);

use Awcodes\Curator\Models\Media;
use MiPress\Core\Services\MediaUrlGenerator;

describe('media url generator', function () {
    it('uses a curation source when the requested variant exists', function () {
        $media = Media::factory()->create([
            'disk' => 'public',
            'directory' => 'entries',
            'path' => 'entries/article.jpg',
            'name' => 'article',
            'ext' => 'jpg',
            'type' => 'image/jpeg',
            'curations' => [
                [
                    'curation' => [
                        'key' => 'thumbnail',
                        'path' => 'entries/article-thumbnail.webp',
                        'url' => '/storage/entries/article-thumbnail.webp',
                    ],
                ],
            ],
        ]);

        $url = app(MediaUrlGenerator::class)->media($media, 'thumbnail');

        // Curation URL is returned directly from storage — no Glide re-processing
        expect($url)->toContain('article-thumbnail.webp')
            ->and($url)->toContain('storage')
            ->and($url)->not->toContain('fm=webp') // not a Glide URL
            ->and(filter_var($url, FILTER_VALIDATE_URL))->not->toBeFalse();
    });

    it('builds glide urls from public paths for uploaded avatars', function () {
        $url = app(MediaUrlGenerator::class)->path('avatars/users/avatar.webp', 'avatar');

        expect($url)->toContain('avatars/users/avatar.webp')
            ->and($url)->toContain('fm=webp')
            ->and($url)->toContain('h=160')
            ->and($url)->toContain('w=160')
            ->and(filter_var($url, FILTER_VALIDATE_URL))->not->toBeFalse();
    });
});
