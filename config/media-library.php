<?php

declare(strict_types=1);

use MiPress\Core\Media\MediaConfig;
use MiPress\Core\Media\SlugFileNamer;
use MiPress\Core\Media\YearMonthPathGenerator;
use MiPress\Core\Models\Media;
use Spatie\MediaLibrary\Conversions\ImageGenerators\Image;
use Spatie\MediaLibrary\Conversions\ImageGenerators\Pdf;
use Spatie\MediaLibrary\Conversions\ImageGenerators\Svg;
use Spatie\MediaLibrary\Conversions\ImageGenerators\Video;
use Spatie\MediaLibrary\Conversions\ImageGenerators\Webp;
use Spatie\MediaLibrary\Conversions\Jobs\PerformConversionsJob;
use Spatie\MediaLibrary\Downloaders\DefaultDownloader;
use Spatie\MediaLibrary\MediaCollections\Models\Observers\MediaObserver;
use Spatie\MediaLibrary\ResponsiveImages\Jobs\GenerateResponsiveImagesJob;
use Spatie\MediaLibrary\ResponsiveImages\TinyPlaceholderGenerator\Blurred;
use Spatie\MediaLibrary\ResponsiveImages\WidthCalculator\FileSizeOptimizedWidthCalculator;
use Spatie\MediaLibrary\Support\FileRemover\DefaultFileRemover;
use Spatie\MediaLibrary\Support\UrlGenerator\DefaultUrlGenerator;

return [
    'disk_name' => MediaConfig::disk(),
    'max_file_size' => MediaConfig::maxUploadSize(),
    'queue_connection_name' => env('MEDIA_QUEUE_CONNECTION', env('QUEUE_CONNECTION', 'sync')),
    'queue_name' => env('MEDIA_QUEUE', ''),
    'queue_conversions_by_default' => true,
    'queue_conversions_after_database_commit' => true,
    'media_model' => Media::class,
    'media_observer' => MediaObserver::class,
    'use_default_collection_serialization' => false,
    'temporary_upload_model' => null,
    'enable_temporary_uploads_session_affinity' => false,
    'generate_thumbnails_for_temporary_uploads' => false,
    'file_namer' => SlugFileNamer::class,
    'path_generator' => YearMonthPathGenerator::class,
    'file_remover_class' => DefaultFileRemover::class,
    'custom_path_generators' => [],
    'url_generator' => DefaultUrlGenerator::class,
    'moves_media_on_update' => false,
    'version_urls' => false,
    'image_optimizers' => [],
    'image_generators' => [
        Image::class,
        Webp::class,
        Pdf::class,
        Svg::class,
        Video::class,
    ],
    'temporary_directory_path' => null,
    'image_driver' => env('IMAGE_DRIVER', 'gd'),
    'ffmpeg_path' => env('FFMPEG_PATH', '/usr/bin/ffmpeg'),
    'ffprobe_path' => env('FFPROBE_PATH', '/usr/bin/ffprobe'),
    'ffmpeg_timeout' => env('FFMPEG_TIMEOUT', 900),
    'ffmpeg_threads' => env('FFMPEG_THREADS', 0),
    'jobs' => [
        'perform_conversions' => PerformConversionsJob::class,
        'generate_responsive_images' => GenerateResponsiveImagesJob::class,
    ],
    'media_downloader' => DefaultDownloader::class,
    'media_downloader_ssl' => env('MEDIA_DOWNLOADER_SSL', true),
    'temporary_url_default_lifetime' => env('MEDIA_TEMPORARY_URL_DEFAULT_LIFETIME', 5),
    'remote' => [
        'extra_headers' => [
            'CacheControl' => 'max-age=604800',
        ],
    ],
    'responsive_images' => [
        'width_calculator' => FileSizeOptimizedWidthCalculator::class,
        'use_tiny_placeholders' => true,
        'tiny_placeholder_generator' => Blurred::class,
    ],
    'enable_vapor_uploads' => false,
    'default_loading_attribute_value' => null,
    'prefix' => 'uploads',
    'force_lazy_loading' => true,
];
