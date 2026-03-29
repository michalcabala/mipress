<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use MiPress\Core\Database\Seeders\PermissionSeeder;
use MiPress\Core\Enums\UserRole;
use MiPress\Core\Models\Blueprint;
use MiPress\Core\Models\Collection;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $this->call(PermissionSeeder::class);
        $this->seedDefaultContentModels();

        $admin = User::query()->updateOrCreate([
            'email' => 'admin@example.com',
        ], [
            'name' => 'Admin',
            'password' => 'password',
            'email_verified_at' => now(),
        ]);

        $admin->syncRoles([UserRole::SuperAdmin->value]);
    }

    private function seedDefaultContentModels(): void
    {
        $pageBlueprint = Blueprint::query()->updateOrCreate(
            ['handle' => 'page'],
            [
                'name' => 'Page',
                'fields' => [
                    [
                        'section' => 'Obsah',
                        'fields' => [
                            [
                                'handle' => 'perex',
                                'label' => 'Perex',
                                'type' => 'textarea',
                            ],
                            [
                                'handle' => 'content',
                                'label' => 'Obsah',
                                'type' => 'mason',
                                'required' => true,
                            ],
                        ],
                    ],
                ],
            ],
        );

        $articleBlueprint = Blueprint::query()->updateOrCreate(
            ['handle' => 'article'],
            [
                'name' => 'Article',
                'fields' => [
                    [
                        'section' => 'Obsah',
                        'fields' => [
                            [
                                'handle' => 'category',
                                'label' => 'Rubrika',
                                'type' => 'text',
                            ],
                            [
                                'handle' => 'excerpt',
                                'label' => 'Perex',
                                'type' => 'textarea',
                                'required' => true,
                            ],
                            [
                                'handle' => 'content',
                                'label' => 'Obsah článku',
                                'type' => 'mason',
                                'required' => true,
                            ],
                        ],
                    ],
                ],
            ],
        );

        Collection::query()->updateOrCreate(
            ['handle' => 'pages'],
            [
                'name' => 'Pages',
                'blueprint_id' => $pageBlueprint->getKey(),
                'icon' => 'far-file',
                'route' => '/{slug}',
                'dated' => false,
                'slugs' => true,
                'hierarchical' => true,
                'sort_direction' => 'asc',
                'sort_order' => 10,
            ],
        );

        Collection::query()->updateOrCreate(
            ['handle' => 'articles'],
            [
                'name' => 'Articles',
                'blueprint_id' => $articleBlueprint->getKey(),
                'icon' => 'far-newspaper',
                'route' => '/journal/{slug}',
                'dated' => true,
                'slugs' => true,
                'hierarchical' => false,
                'sort_direction' => 'desc',
                'sort_order' => 20,
            ],
        );
    }
}
