<?php

namespace App\Filament\Clusters\KycManagement\Resources;

use App\Filament\Clusters\KycManagement;
use App\Filament\Clusters\KycManagement\Resources\KycTemplateResource\Pages;
use App\Filament\Clusters\KycManagement\Resources\KycTemplateResource\RelationManagers;
use App\Models\KycTemplate;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class KycTemplateResource extends Resource
{
    protected static ?string $model = KycTemplate::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $cluster = KycManagement::class;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('title')
                    ->required()
                    ->maxLength(255),
                Forms\Components\Textarea::make('description')
                    ->maxLength(65535)
                    ->columnSpanFull(),
                Forms\Components\CheckboxList::make('applicable_to')
                    ->options([
                        'user' => 'User',
                        'merchant' => 'Merchant',
                    ])
                    ->columns(2)
                    ->required(),
                Forms\Components\Repeater::make('form_fields')
                    ->label('Fields')
                    ->schema([
                        Forms\Components\TextInput::make('label')
                            ->required()
                            ->columnSpan(4),
                        Forms\Components\Select::make('type')
                            ->options([
                                'text' => 'Input Text',
                                'file' => 'File',
                                'number' => 'Number',
                            ])
                            ->required()
                            ->columnSpan(4),
                        Forms\Components\Select::make('required')
                            ->options([
                                'true' => 'Required',
                                'false' => 'Optional',
                            ])
                            ->required()
                            ->columnSpan(4),
                    ])
                    ->columns(12)
                    ->columnSpanFull(),
                Forms\Components\Toggle::make('is_active')
                    ->label('Status')
                    ->required()
                    ->default(true),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->label('Title | Description')
                    ->description(fn (KycTemplate $record): string => str($record->description)->limit(50))
                    ->searchable(),
                Tables\Columns\TextColumn::make('applicable_to')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => ucfirst($state))
                    ->color(fn (string $state): string => match ($state) {
                        'user' => 'info',
                        'merchant' => 'warning',
                        default => 'gray',
                    }),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Status')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->modalWidth('xl'),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListKycTemplates::route('/'),
        ];
    }
}
