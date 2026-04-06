<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use MiPress\Core\Models\Blueprint;
use MiPress\Core\Models\Setting;

class SettingsSeeder extends Seeder
{
    public function run(): void
    {
        $generalBlueprint = Blueprint::query()->updateOrCreate(
            ['handle' => 'settings_general'],
            [
                'name' => 'Settings - Obecné',
                'fields' => [
                    [
                        'section' => 'Obecné',
                        'fields' => [
                            ['handle' => 'site_name', 'label' => 'Název webu', 'type' => 'text', 'required' => true],
                            ['handle' => 'site_description', 'label' => 'Popis webu', 'type' => 'textarea'],
                            ['handle' => 'logo', 'label' => 'Logo', 'type' => 'media'],
                            ['handle' => 'favicon', 'label' => 'Favicon', 'type' => 'media'],
                        ],
                    ],
                ],
            ],
        );

        $contactBlueprint = Blueprint::query()->updateOrCreate(
            ['handle' => 'settings_contact'],
            [
                'name' => 'Settings - Kontakt',
                'fields' => [
                    [
                        'section' => 'Kontakt',
                        'fields' => [
                            ['handle' => 'email', 'label' => 'E-mail', 'type' => 'text'],
                            ['handle' => 'phone', 'label' => 'Telefon', 'type' => 'text'],
                            ['handle' => 'address', 'label' => 'Adresa', 'type' => 'textarea'],
                            ['handle' => 'ico', 'label' => 'IČO', 'type' => 'text'],
                            ['handle' => 'dic', 'label' => 'DIČ', 'type' => 'text'],
                        ],
                    ],
                ],
            ],
        );

        $socialBlueprint = Blueprint::query()->updateOrCreate(
            ['handle' => 'settings_social'],
            [
                'name' => 'Settings - Sociální sítě',
                'fields' => [
                    [
                        'section' => 'Sociální sítě',
                        'fields' => [
                            [
                                'handle' => 'networks',
                                'label' => 'Sítě',
                                'type' => 'repeater',
                                'config' => [
                                    'fields' => [
                                        ['handle' => 'name', 'label' => 'Název', 'type' => 'text'],
                                        ['handle' => 'url', 'label' => 'URL', 'type' => 'text'],
                                        ['handle' => 'icon', 'label' => 'Ikona (FA class)', 'type' => 'text'],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        );

        $seoBlueprint = Blueprint::query()->updateOrCreate(
            ['handle' => 'settings_seo'],
            [
                'name' => 'Settings - SEO',
                'fields' => [
                    [
                        'section' => 'SEO',
                        'fields' => [
                            [
                                'handle' => 'meta_title_suffix',
                                'label' => 'Suffix meta titulku',
                                'type' => 'text',
                                'config' => ['placeholder' => ' | Název webu'],
                            ],
                            ['handle' => 'meta_description', 'label' => 'Výchozí meta popis', 'type' => 'textarea'],
                            [
                                'handle' => 'robots',
                                'label' => 'Robots',
                                'type' => 'select',
                                'config' => [
                                    'options' => [
                                        'index, follow' => 'index, follow',
                                        'noindex, nofollow' => 'noindex, nofollow',
                                        'noindex, follow' => 'noindex, follow',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        );

        $scriptsBlueprint = Blueprint::query()->updateOrCreate(
            ['handle' => 'settings_scripts'],
            [
                'name' => 'Settings - Skripty',
                'fields' => [
                    [
                        'section' => 'Skripty',
                        'fields' => [
                            [
                                'handle' => 'head_scripts',
                                'label' => 'Skripty v <head>',
                                'type' => 'textarea',
                                'config' => ['rows' => 6],
                            ],
                            [
                                'handle' => 'body_start_scripts',
                                'label' => 'Skripty za <body>',
                                'type' => 'textarea',
                                'config' => ['rows' => 6],
                            ],
                            [
                                'handle' => 'body_end_scripts',
                                'label' => 'Skripty před </body>',
                                'type' => 'textarea',
                                'config' => ['rows' => 6],
                            ],
                        ],
                    ],
                ],
            ],
        );

        Setting::query()->updateOrCreate(
            ['handle' => 'general'],
            ['name' => 'Obecné', 'blueprint_id' => $generalBlueprint->id, 'icon' => 'fal-globe', 'sort_order' => 10, 'data' => []],
        );

        Setting::query()->updateOrCreate(
            ['handle' => 'contact'],
            ['name' => 'Kontakt', 'blueprint_id' => $contactBlueprint->id, 'icon' => 'fal-address-book', 'sort_order' => 20, 'data' => []],
        );

        Setting::query()->updateOrCreate(
            ['handle' => 'social'],
            ['name' => 'Sociální sítě', 'blueprint_id' => $socialBlueprint->id, 'icon' => 'fal-share-nodes', 'sort_order' => 30, 'data' => []],
        );

        Setting::query()->updateOrCreate(
            ['handle' => 'seo'],
            ['name' => 'SEO', 'blueprint_id' => $seoBlueprint->id, 'icon' => 'fal-search', 'sort_order' => 40, 'data' => []],
        );

        Setting::query()->updateOrCreate(
            ['handle' => 'scripts'],
            ['name' => 'Skripty', 'blueprint_id' => $scriptsBlueprint->id, 'icon' => 'fal-code', 'sort_order' => 50, 'data' => []],
        );
    }
}
