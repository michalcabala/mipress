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
                Section::make(__('admin.profile.sections.profile'))
                    ->schema([
                        FileUpload::make('avatar_path')
                            ->label(__('admin.profile.fields.avatar'))
                            ->helperText(__('admin.profile.help.avatar'))
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
                Section::make(__('admin.profile.sections.password'))
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
