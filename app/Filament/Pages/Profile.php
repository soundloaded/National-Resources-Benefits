<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;

use Filament\Forms\Form;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Section;

class Profile extends \Filament\Pages\Auth\EditProfile
{
    protected static ?string $navigationIcon = 'heroicon-o-user';
    
    protected static bool $shouldRegisterNavigation = false;

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Profile Information')
                    ->schema([
                        FileUpload::make('avatar_url')
                            ->avatar()
                            ->label('Profile Photo')
                            ->directory('avatars')
                            ->image()
                            ->imageEditor(),
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('email')
                            ->email()
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true),
                    ]),
                Section::make('Update Password')
                    ->schema([
                        TextInput::make('password')
                            ->password()
                            ->dehydrated(fn ($state) => filled($state))
                            ->confirmed(),
                        TextInput::make('password_confirmation')
                            ->password()
                            ->dehydrated(false),
                    ]),
            ]);
    }
}
