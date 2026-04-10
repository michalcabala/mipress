<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use Filament\Auth\Pages\EditProfile as BaseEditProfile;
use Filament\Forms\Components\FileUpload;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class EditProfile extends BaseEditProfile
{
    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Profil')
                    ->schema([
                        FileUpload::make('avatar_path')
                            ->label('Avatar')
                            ->helperText('Profilová fotka zobrazená v administraci.')
                            ->avatar()
                            ->imageEditor()
                            ->circleCropper()
                            ->disk('public')
                            ->directory('avatars/users')
                            ->visibility('public')
                            ->moveFiles()
                            ->maxSize(2048)
                            ->imageResizeTargetWidth('512')
                            ->imageResizeTargetHeight('512'),
                        $this->getNameFormComponent(),
                        $this->getEmailFormComponent(),
                    ]),
                Section::make('Změna hesla')
                    ->schema([
                        $this->getPasswordFormComponent(),
                        $this->getPasswordConfirmationFormComponent(),
                        $this->getCurrentPasswordFormComponent(),
                    ])
                    ->collapsible()
                    ->collapsed(),
            ]);
    }
}
