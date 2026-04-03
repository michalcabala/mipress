<?php

declare(strict_types=1);

namespace MiPress\Forms\Filament\Resources\FormResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Str;
use MiPress\Forms\Filament\Resources\FormResource;
use MiPress\Forms\Models\FormField;

class CreateForm extends CreateRecord
{
    protected static string $resource = FormResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (! filled($data['handle'] ?? null)) {
            $data['handle'] = Str::slug((string) ($data['title'] ?? 'form'));
        }

        if (($data['template'] ?? null) === 'contact') {
            $data['fields'] = FormField::contactTemplate();
        }

        unset($data['template']);

        return $data;
    }
}
