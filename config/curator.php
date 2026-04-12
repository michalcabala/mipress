<?php

declare(strict_types=1);
use App\Filament\Resources\CuratorMediaResource;
use App\Filament\Resources\CuratorMediaResource\Pages\CreateCuratorMedia;
use App\Filament\Resources\CuratorMediaResource\Pages\EditCuratorMedia;
use App\Filament\Resources\CuratorMediaResource\Pages\ListCuratorMedia;
use App\Filament\Resources\CuratorMediaResource\Schemas\CuratorMediaForm;
use App\Models\CuratorMedia;
use Awcodes\Curator\Enums\PreviewableExtensions;
use Awcodes\Curator\Providers\GlideUrlProvider;
use Awcodes\Curator\Resources\Media\Tables\MediaTable;

return [
    'curation_formats' => PreviewableExtensions::toArray(),
    'default_disk' => env('CURATOR_DEFAULT_DISK', 'local_uploads'),
    'default_directory' => null,
    'default_visibility' => 'public',
    'features' => [
        'curations' => true,
        'file_swap' => true,
        'directory_restriction' => false,
        'preserve_file_names' => false,
        'tenancy' => [
            'enabled' => false,
            'relationship_name' => null,
        ],
    ],
    'glide_token' => env('CURATOR_GLIDE_TOKEN'),
    'model' => CuratorMedia::class,
    'path_generator' => null,
    'resource' => [
        'label' => 'Media',
        'plural_label' => 'Media',
        'default_layout' => 'grid',
        'navigation' => [
            'group' => null,
            'icon' => 'fal-photo-video-film',
            'sort' => null,
            'should_register' => true,
            'should_show_badge' => false,
        ],
        'resource' => CuratorMediaResource::class,
        'pages' => [
            'create' => CreateCuratorMedia::class,
            'edit' => EditCuratorMedia::class,
            'index' => ListCuratorMedia::class,
        ],
        'schemas' => [
            'form' => CuratorMediaForm::class,
        ],
        'tables' => [
            'table' => MediaTable::class,
        ],
    ],
    'url_provider' => GlideUrlProvider::class,
];
