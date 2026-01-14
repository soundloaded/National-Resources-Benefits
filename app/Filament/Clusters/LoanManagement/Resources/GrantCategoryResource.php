<?php

namespace App\Filament\Clusters\LoanManagement\Resources;

use App\Filament\Clusters\LoanManagement;
use App\Filament\Clusters\LoanManagement\Resources\GrantCategoryResource\Pages;
use App\Models\GrantCategory;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class GrantCategoryResource extends Resource
{
    protected static ?string $model = GrantCategory::class;

    protected static ?string $navigationIcon = 'heroicon-o-tag';

    protected static ?string $cluster = LoanManagement::class;

    protected static ?string $navigationLabel = 'Grant Categories';
    
    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Category Details')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->unique(GrantCategory::class, 'name', ignoreRecord: true)
                            ->maxLength(255),
                        
                        Forms\Components\Textarea::make('description')
                            ->rows(3)
                            ->columnSpanFull(),

                        Forms\Components\Grid::make(2)->schema([
                            Forms\Components\Select::make('icon')
                                ->label('Icon')
                                ->options([
                                    'heroicon-o-briefcase' => 'Briefcase (Business)',
                                    'heroicon-o-book-open' => 'Book (Education)',
                                    'heroicon-o-building-office' => 'Building (Real Estate)',
                                    'heroicon-o-heart' => 'Heart (Healthcare)',
                                    'heroicon-o-sparkles' => 'Sparkles (Technology)',
                                    'heroicon-o-users' => 'Users (Community)',
                                    'heroicon-o-exclamation-triangle' => 'Warning (Emergency)',
                                    'heroicon-o-currency-dollar' => 'Dollar (Finance)',
                                ])
                                ->searchable(),

                            Forms\Components\Select::make('color')
                                ->label('Badge Color')
                                ->options([
                                    'primary' => 'Primary (Blue)',
                                    'success' => 'Success (Green)',
                                    'danger' => 'Danger (Red)',
                                    'warning' => 'Warning (Yellow)',
                                    'info' => 'Info (Cyan)',
                                    'gray' => 'Gray',
                                ])
                                ->default('primary'),
                        ]),
                    ]),

                Forms\Components\Section::make('Status')
                    ->schema([
                        Forms\Components\Toggle::make('is_active')
                            ->label('Active')
                            ->default(true),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                
                Tables\Columns\TextColumn::make('description')
                    ->limit(50)
                    ->wrap()
                    ->color('gray'),
                
                Tables\Columns\TextColumn::make('grants_count')
                    ->label('Grants')
                    ->counts('grants')
                    ->badge()
                    ->color('info'),
                
                Tables\Columns\TextColumn::make('icon')
                    ->badge()
                    ->searchable(),
                
                Tables\Columns\TextColumn::make('color')
                    ->badge()
                    ->color(fn (string $state): string => $state),
                
                Tables\Columns\BooleanColumn::make('is_active')
                    ->label('Active'),
                
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
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
            'index' => Pages\ListGrantCategories::route('/'),
            'create' => Pages\CreateGrantCategory::route('/create'),
            'edit' => Pages\EditGrantCategory::route('/{record}/edit'),
        ];
    }
}
