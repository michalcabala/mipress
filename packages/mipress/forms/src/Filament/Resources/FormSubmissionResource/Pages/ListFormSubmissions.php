<?php

declare(strict_types=1);

namespace MiPress\Forms\Filament\Resources\FormSubmissionResource\Pages;

use Filament\Resources\Pages\ListRecords;
use MiPress\Forms\Filament\Resources\FormSubmissionResource;

class ListFormSubmissions extends ListRecords
{
    protected static string $resource = FormSubmissionResource::class;
}
