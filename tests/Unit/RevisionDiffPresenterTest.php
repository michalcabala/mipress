<?php

declare(strict_types=1);

use MiPress\Core\Services\RevisionDiffPresenter;

describe('revision diff presenter', function () {
    it('renders grouped changed-only field sections with friendly labels', function () {
        $presenter = app(RevisionDiffPresenter::class);

        $html = $presenter->renderComparison(
            [
                'title' => 'Puvodni titulek',
                'slug' => 'puvodni-titulek',
                'meta_title' => 'Puvodni SEO',
                'status' => 'draft',
                'data' => [
                    'subtitle' => 'Puvodni podtitulek',
                    'teaser' => 'Stejny teaser',
                ],
            ],
            [
                'title' => 'Novy titulek',
                'slug' => 'puvodni-titulek',
                'meta_title' => 'Nove SEO',
                'status' => 'draft',
                'data' => [
                    'subtitle' => 'Novy podtitulek',
                    'teaser' => 'Stejny teaser',
                ],
            ],
            'Leva verze',
            'Prava verze',
        )->toHtml();

        expect($html)
            ->toContain('Základ')
            ->toContain('SEO')
            ->toContain('Vlastní pole')
            ->toContain('Titulek')
            ->toContain('SEO titulek')
            ->toContain('Vlastní pole: Subtitle')
            ->not->toContain('meta_title')
            ->not->toContain('Stejny teaser');
    });

    it('detects Mason block moves using a block heuristic', function () {
        $presenter = app(RevisionDiffPresenter::class);

        $html = $presenter->renderComparison(
            [
                'content' => [
                    [
                        'id' => 'a1',
                        'type' => 'paragraph',
                        'data' => ['text' => 'Prvni blok'],
                    ],
                    [
                        'id' => 'b2',
                        'type' => 'paragraph',
                        'data' => ['text' => 'Druhy blok'],
                    ],
                ],
            ],
            [
                'content' => [
                    [
                        'id' => 'b2',
                        'type' => 'paragraph',
                        'data' => ['text' => 'Druhy blok'],
                    ],
                    [
                        'id' => 'a1',
                        'type' => 'paragraph',
                        'data' => ['text' => 'Prvni blok'],
                    ],
                ],
            ],
            'Leva verze',
            'Prava verze',
        )->toHtml();

        expect($html)
            ->toContain('Rich diff bloků s heuristikou přesunů.')
            ->toContain('Přesunutý blok')
            ->toContain('Pozice #1')
            ->toContain('Pozice #2');
    });

    it('renders old and new Mason payload for changed blocks', function () {
        $presenter = app(RevisionDiffPresenter::class);

        $html = $presenter->renderComparison(
            [
                'content' => [
                    [
                        'id' => 'x1',
                        'type' => 'paragraph',
                        'data' => ['text' => 'Stary text'],
                    ],
                ],
            ],
            [
                'content' => [
                    [
                        'id' => 'x1',
                        'type' => 'paragraph',
                        'data' => ['text' => 'Novy text'],
                    ],
                ],
            ],
            'Leva verze',
            'Prava verze',
        )->toHtml();

        expect($html)
            ->toContain('Upravený blok')
            ->toContain('Stary text')
            ->toContain('Novy text');
    });
});
