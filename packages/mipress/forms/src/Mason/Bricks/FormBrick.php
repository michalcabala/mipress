<?php

declare(strict_types=1);

namespace MiPress\Forms\Mason\Bricks;

use Awcodes\Mason\Brick;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use MiPress\Forms\Models\Form;

class FormBrick extends Brick
{
    public static function getId(): string
    {
        return 'form';
    }

    public static function getLabel(): string
    {
        return 'Formular';
    }

    public static function getIcon(): string
    {
        return 'heroicon-o-envelope';
    }

    public static function toHtml(array $config, ?array $data = null): ?string
    {
        return view('mipress-forms::blocks.form', [
            'formHandle' => $config['form_handle'] ?? null,
        ])->render();
    }

    public static function configureBrickAction(Action $action): Action
    {
        return $action
            ->slideOver()
            ->schema([
                Select::make('form_handle')
                    ->label('Formular')
                    ->options(fn (): array => Form::query()
                        ->where('is_active', true)
                        ->orderBy('title')
                        ->pluck('title', 'handle')
                        ->all())
                    ->searchable()
                    ->required(),
            ]);
    }
}
