<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use MiPress\Core\Models\Blueprint;
use MiPress\Core\Models\Collection;
use MiPress\Core\Models\Entry;

class MiPressDemoSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::first() ?? User::factory()->create([
            'name' => 'Admin',
            'email' => 'admin@example.com',
        ]);

        $blueprint = Blueprint::firstOrCreate(
            ['handle' => 'stranka'],
            [
                'name' => 'Stránka',
                'fields' => [
                    [
                        'section' => 'Obsah stránky',
                        'fields' => [
                            ['handle' => 'content', 'type' => 'mason', 'label' => 'Obsah', 'required' => false],
                            ['handle' => 'meta_title', 'type' => 'text', 'label' => 'Meta titulek', 'required' => false],
                            ['handle' => 'meta_description', 'type' => 'textarea', 'label' => 'Meta popis', 'required' => false],
                        ],
                    ],
                ],
            ]
        );

        $pages = Collection::firstOrCreate(
            ['handle' => 'pages'],
            [
                'name' => 'Stránky',
                'blueprint_id' => $blueprint->id,
                'icon' => 'fal-file-lines',
                'route' => '/{slug}',
                'dated' => false,
                'slugs' => true,
                'sort_direction' => 'asc',
                'sort_order' => 1,
            ]
        );

        $articles = Collection::firstOrCreate(
            ['handle' => 'articles'],
            [
                'name' => 'Články',
                'blueprint_id' => $blueprint->id,
                'icon' => 'fal-newspaper',
                'route' => '/clanky/{slug}',
                'dated' => true,
                'slugs' => true,
                'sort_direction' => 'desc',
                'sort_order' => 2,
            ]
        );

        if (! Entry::where('collection_id', $pages->id)->exists()) {
            Entry::create([
                'collection_id' => $pages->id,
                'blueprint_id' => $blueprint->id,
                'title' => 'Úvodní stránka',
                'slug' => 'uvodni-stranka',
                'status' => 'published',
                'author_id' => $user->id,
                'data' => [
                    'meta_title' => 'Úvodní stránka | miPress',
                    'meta_description' => 'Vítejte na webu postaveném na miPress CMS.',
                ],
                'published_at' => now(),
                'sort_order' => 1,
            ]);
        }

        if (! Entry::where('collection_id', $articles->id)->exists()) {
            Entry::create([
                'collection_id' => $articles->id,
                'blueprint_id' => $blueprint->id,
                'title' => 'První článek',
                'slug' => 'prvni-clanek',
                'status' => 'published',
                'author_id' => $user->id,
                'data' => [
                    'meta_title' => 'První článek | miPress',
                ],
                'published_at' => now(),
                'sort_order' => 1,
            ]);
        }
    }
}
