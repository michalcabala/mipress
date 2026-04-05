<?php

declare(strict_types=1);

use App\Models\User;
use MiPress\Core\Database\Seeders\PermissionSeeder;
use MiPress\Core\Enums\EntryStatus;
use MiPress\Core\Enums\UserRole;
use MiPress\Core\Filament\Pages\BotlyPage;
use MiPress\Core\Filament\Pages\SitemapSettings;
use MiPress\Core\Filament\Resources\BlueprintResource;
use MiPress\Core\Filament\Resources\CollectionResource;
use MiPress\Core\Filament\Resources\EntryResource;
use MiPress\Core\Filament\Resources\GlobalSetResource;
use MiPress\Core\Filament\Resources\PageResource;
use MiPress\Core\Filament\Resources\TaxonomyResource;
use MiPress\Core\Filament\Resources\TermResource;
use MiPress\Core\Models\Blueprint;
use MiPress\Core\Models\Collection;
use MiPress\Core\Models\Page;
use MiPress\Core\Models\Setting;
use MiPress\Forms\Filament\Pages\FormNotificationSettings;
use MiPress\Forms\Filament\Resources\FormResource;
use MiPress\Forms\Filament\Resources\FormSubmissionResource;
use MiPress\Forms\Models\Form;
use MiPress\Forms\Models\FormSubmission;

beforeEach(function () {
    $this->seed(PermissionSeeder::class);
});

describe('production smoke', function () {
    it('serves critical public endpoints', function () {
        $homepage = Page::factory()->create([
            'title' => 'Domovska stranka',
            'slug' => 'domovska-stranka',
            'status' => EntryStatus::Published,
            'published_at' => now(),
        ]);

        Setting::putValue('site.homepage_page_id', (string) $homepage->getKey());

        $this->get('/')->assertSuccessful();
        $this->get('/theme-files/default/assets/css/theme.css')->assertSuccessful();
    });

    it('serves critical admin endpoints for superadmin', function () {
        $superAdmin = User::factory()->create();
        $superAdmin->assignRole(UserRole::SuperAdmin->value);

        $blueprint = Blueprint::factory()->create([
            'handle' => 'page',
            'fields' => [],
        ]);

        Collection::factory()->create([
            'name' => 'Stranky',
            'handle' => 'pages',
            'blueprint_id' => $blueprint->id,
            'route' => '/{slug}',
            'slugs' => true,
            'dated' => false,
            'hierarchical' => true,
        ]);

        $this->actingAs($superAdmin);

        $adminUrls = [
            BlueprintResource::getUrl('index'),
            CollectionResource::getUrl('index'),
            EntryResource::getUrl('index', ['collection' => 'pages']),
            PageResource::getUrl('index'),
            TaxonomyResource::getUrl('index'),
            TermResource::getUrl('index'),
            GlobalSetResource::getUrl('index'),
            FormResource::getUrl('index'),
            FormSubmissionResource::getUrl('index'),
            FormNotificationSettings::getUrl(),
            BotlyPage::getUrl(),
            SitemapSettings::getUrl(),
        ];

        foreach ($adminUrls as $url) {
            $this->get($url)->assertSuccessful();
        }
    });

    it('accepts a basic public form submission', function () {
        $form = Form::query()->create([
            'title' => 'Kontaktni formular',
            'handle' => 'kontaktni-formular-smoke',
            'fields' => [
                [
                    'handle' => 'name',
                    'type' => 'text',
                    'label' => 'Jmeno',
                    'required' => true,
                    'config' => [],
                    'order' => 1,
                ],
                [
                    'handle' => 'email',
                    'type' => 'email',
                    'label' => 'Email',
                    'required' => true,
                    'config' => [],
                    'order' => 2,
                ],
            ],
            'recipients' => [],
            'spam_protection' => 'honeypot',
            'is_active' => true,
        ]);

        $this->post(route('mipress.form.submit', ['form' => $form->handle]), [
            '_form_started_at' => time() - 5,
            'website' => '',
            'name' => 'Jan Novak',
            'email' => 'jan@example.com',
        ])->assertRedirect();

        expect(FormSubmission::query()->where('form_id', $form->getKey())->exists())->toBeTrue();
    });
});
