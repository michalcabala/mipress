<?php

declare(strict_types=1);
use App\Filament\Resources\CuratorMediaResource;
use App\Filament\Resources\CuratorMediaResource\Pages\CreateCuratorMedia;
use App\Filament\Resources\CuratorMediaResource\Pages\EditCuratorMedia;
use App\Filament\Resources\CuratorMediaResource\Pages\ListCuratorMedia;
use Awcodes\Curator\Enums\PreviewableExtensions;
use Awcodes\Curator\Models\Media;
use Awcodes\Curator\Providers\GlideUrlProvider;
use Awcodes\Curator\Resources\Media\Schemas\MediaForm;
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
    'model' => Media::class,
    'path_generator' => null,
    'resource' => [
        'label' => 'Media',
        'plural_label' => 'Media',
        'default_layout' => 'grid',
        'navigation' => [
            'group' => null,
            'icon' => 'heroicon-o-photo',
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
            'form' => MediaForm::class,
        ],
        'tables' => [
            'table' => MediaTable::class,
        ],
    ],
    'url_provider' => GlideUrlProvider::class,
];
