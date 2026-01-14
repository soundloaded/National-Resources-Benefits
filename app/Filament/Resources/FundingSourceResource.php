<?php

namespace App\Filament\Resources;

use App\Filament\Resources\FundingSourceResource\Pages;
use App\Filament\Resources\FundingSourceResource\RelationManagers;
use App\Models\FundingSource;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class FundingSourceResource extends Resource
{
    protected static ?string $model = FundingSource::class;

    protected static ?string $navigationIcon = 'heroicon-o-currency-dollar';
    protected static ?string $navigationGroup = 'Content Management';
    protected static ?int $navigationSort = 6;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Funding Details')
                    ->schema([
                        Forms\Components\TextInput::make('title')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),
                        
                        Forms\Components\RichEditor::make('description')
                            ->columnSpanFull(),
                        
                        Forms\Components\Grid::make(2)->schema([
                            Forms\Components\TextInput::make('amount_min')
                                ->label('Min Amount')
                                ->numeric()
                                ->prefix('$'),
                            Forms\Components\TextInput::make('amount_max')
                                ->label('Max Amount')
                                ->numeric()
                                ->prefix('$'),
                        ]),
                        
                        Forms\Components\Select::make('funding_category_id')
                            ->label('Category')
                            ->relationship('fundingCategory', 'name')
                            ->searchable()
                            ->required()
                            ->helperText('Manage categories from the table header.'),
                            
                        Forms\Components\DatePicker::make('deadline'),
                        
                        Forms\Components\Toggle::make('is_active')
                            ->default(true),
                    ]),
                
                Forms\Components\Section::make('Application Settings')
                    ->description('Configure how users can apply for this funding')
                    ->schema([
                        Forms\Components\Toggle::make('is_internal')
                            ->label('Accept Applications on Platform')
                            ->helperText('Enable to allow users to apply directly from this platform')
                            ->default(true)
                            ->live(),
                        
                        Forms\Components\TextInput::make('url')
                            ->label('External Application URL')
                            ->url()
                            ->suffixIcon('heroicon-m-globe-alt')
                            ->visible(fn ($get) => !$get('is_internal'))
                            ->helperText('Users will be redirected to this URL to apply'),
                        
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('total_slots')
                                    ->label('Total Available Slots')
                                    ->numeric()
                                    ->helperText('Leave empty for unlimited')
                                    ->placeholder('Unlimited'),
                                
                                Forms\Components\TextInput::make('max_applications_per_user')
                                    ->label('Max Applications Per User')
                                    ->numeric()
                                    ->default(1)
                                    ->helperText('How many times a user can apply'),
                            ])
                            ->visible(fn ($get) => $get('is_internal')),
                        
                        Forms\Components\Textarea::make('requirements')
                            ->label('Eligibility Requirements')
                            ->rows(4)
                            ->helperText('One requirement per line. These will be shown to applicants.')
                            ->placeholder("Must be 18 years or older\nValid ID required\nMust be a resident")
                            ->visible(fn ($get) => $get('is_internal'))
                            ->columnSpanFull(),
                        
                        Forms\Components\Repeater::make('form_fields')
                            ->label('Custom Application Fields')
                            ->schema([
                                Forms\Components\TextInput::make('name')
                                    ->label('Field Name')
                                    ->required()
                                    ->placeholder('e.g., business_name'),
                                Forms\Components\TextInput::make('label')
                                    ->required()
                                    ->placeholder('e.g., Business Name'),
                                Forms\Components\Select::make('type')
                                    ->options([
                                        'text' => 'Text Input',
                                        'textarea' => 'Text Area',
                                        'number' => 'Number',
                                        'select' => 'Dropdown',
                                        'file' => 'File Upload',
                                    ])
                                    ->default('text')
                                    ->required(),
                                Forms\Components\Toggle::make('required')
                                    ->default(true),
                            ])
                            ->columns(4)
                            ->visible(fn ($get) => $get('is_internal'))
                            ->collapsible()
                            ->defaultItems(0)
                            ->helperText('Add custom fields to collect additional information from applicants')
                            ->columnSpanFull(),
                    ])
                    ->collapsible(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                
                Tables\Columns\TextColumn::make('fundingCategory.name')
                    ->label('Category')
                    ->badge()
                    ->color('primary'),
                
                Tables\Columns\TextColumn::make('amount_min')
                    ->money('USD')
                    ->label('Min Amount'),
                    
                Tables\Columns\TextColumn::make('amount_max')
                    ->money('USD')
                    ->label('Max Amount'),
                
                Tables\Columns\IconColumn::make('is_internal')
                    ->label('Internal')
                    ->boolean(),
                
                Tables\Columns\TextColumn::make('applications_count')
                    ->label('Applications')
                    ->counts('applications')
                    ->badge()
                    ->color('info'),
                    
                Tables\Columns\TextColumn::make('deadline')
                    ->date()
                    ->sortable()
                    ->color(fn ($record) => $record->deadline && $record->deadline < now() ? 'danger' : 'success'),
                    
                Tables\Columns\IconColumn::make('is_active')
                    ->boolean(),
            ])

            ->filters([
                Tables\Filters\SelectFilter::make('is_internal')
                    ->label('Application Type')
                    ->options([
                        '1' => 'Internal',
                        '0' => 'External',
                    ]),
                Tables\Filters\SelectFilter::make('funding_category_id')
                    ->label('Category')
                    ->relationship('fundingCategory', 'name'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
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
            RelationManagers\ApplicationsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListFundingSources::route('/'),
            'create' => Pages\CreateFundingSource::route('/create'),
            'edit' => Pages\EditFundingSource::route('/{record}/edit'),
        ];
    }
}
