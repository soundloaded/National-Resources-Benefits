<?php

namespace App\Filament\Resources;

use App\Filament\Resources\FundingCategoryResource\Pages;
use App\Models\FundingCategory;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class FundingCategoryResource extends Resource
{
    protected static ?string $model = FundingCategory::class;

    protected static ?string $navigationIcon = 'heroicon-o-tag';
    protected static ?string $navigationGroup = 'Content Management';
    protected static ?int $navigationSort = 5;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('slug')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(255),
                Forms\Components\Textarea::make('description')
                    ->rows(3)
                    ->columnSpanFull(),
                Forms\Components\Toggle::make('is_active')
                    ->label('Active')
                    ->default(true),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('slug')->searchable(),
                Tables\Columns\IconColumn::make('is_active')->boolean(),
                Tables\Columns\TextColumn::make('created_at')->dateTime()->sortable(),
            ])
            ->actions([
                Tables\Actions\Action::make('edit')
                    ->label('Edit')
                    ->icon('heroicon-o-pencil')
                    ->modalHeading('Edit Funding Category')
                    ->modalWidth('md')
                    ->fillForm(fn (FundingCategory $record): array => $record->attributesToArray())
                    ->form([
                        Forms\Components\TextInput::make('name')
                            ->label('Name')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('slug')
                            ->label('Slug')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255),
                        Forms\Components\Textarea::make('description')
                            ->label('Description')
                            ->rows(3)
                            ->columnSpanFull(),
                        Forms\Components\Toggle::make('is_active')
                            ->label('Active'),
                    ])
                    ->action(function (FundingCategory $record, array $data) {
                        $record->update($data);

                        \Filament\Notifications\Notification::make()
                            ->title('Category updated')
                            ->success()
                            ->send();
                    }),
                Tables\Actions\DeleteAction::make(),
            ])
            ->headerActions([
                Tables\Actions\Action::make('create')
                    ->label('New Category')
                    ->icon('heroicon-o-plus')
                    ->modalHeading('Create Funding Category')
                    ->modalWidth('md')
                    ->form([
                        Forms\Components\TextInput::make('name')
                            ->label('Name')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('slug')
                            ->label('Slug')
                            ->helperText('Optional; generated from name if empty.'),
                        Forms\Components\Textarea::make('description')
                            ->label('Description')
                            ->rows(3)
                            ->columnSpanFull(),
                        Forms\Components\Toggle::make('is_active')
                            ->label('Active')
                            ->default(true),
                    ])
                    ->action(function (array $data) {
                        $slug = $data['slug'] ?? $data['name'];
                        $slug = \Illuminate\Support\Str::slug($slug);

                        if (FundingCategory::where('slug', $slug)->exists()) {
                            \Filament\Notifications\Notification::make()
                                ->title('Slug already exists')
                                ->danger()
                                ->send();
                            return;
                        }

                        FundingCategory::create([
                            'name' => $data['name'],
                            'slug' => $slug,
                            'description' => $data['description'] ?? null,
                            'is_active' => (bool) ($data['is_active'] ?? true),
                        ]);

                        \Filament\Notifications\Notification::make()
                            ->title('Category created')
                            ->success()
                            ->send();
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListFundingCategories::route('/'),
            'create' => Pages\CreateFundingCategory::route('/create'),
        ];
    }
}
