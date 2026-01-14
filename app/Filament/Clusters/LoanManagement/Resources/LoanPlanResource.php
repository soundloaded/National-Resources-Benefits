<?php

namespace App\Filament\Clusters\LoanManagement\Resources;

use App\Filament\Clusters\LoanManagement;
use App\Filament\Clusters\LoanManagement\Resources\LoanPlanResource\Pages;
use App\Models\LoanPlan;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class LoanPlanResource extends Resource
{
    protected static ?string $model = LoanPlan::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationLabel = 'Loan Plans';

    protected static ?string $modelLabel = 'Loan Plan';

    protected static ?string $pluralModelLabel = 'Loan Plans';

    protected static ?string $cluster = LoanManagement::class;
    
    protected static ?int $navigationSort = 1;

    protected static ?string $slug = 'loans';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Basic Information')
                    ->description('Set the loan plan name and description')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn (Forms\Set $set, ?string $state) => $set('slug', Str::slug($state))),

                        Forms\Components\TextInput::make('slug')
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true),

                        Forms\Components\TextInput::make('tagline')
                            ->placeholder('e.g., Fast approval for urgent needs')
                            ->maxLength(255),

                        Forms\Components\Textarea::make('description')
                            ->rows(3)
                            ->columnSpanFull(),
                    ])->columns(2),

                Forms\Components\Section::make('Loan Amounts')
                    ->description('Configure minimum and maximum loan amounts')
                    ->schema([
                        Forms\Components\TextInput::make('min_amount')
                            ->required()
                            ->numeric()
                            ->prefix('$')
                            ->default(100),

                        Forms\Components\TextInput::make('max_amount')
                            ->required()
                            ->numeric()
                            ->prefix('$')
                            ->default(10000),

                        Forms\Components\TextInput::make('interest_rate')
                            ->required()
                            ->numeric()
                            ->suffix('% / month')
                            ->default(5)
                            ->helperText('Monthly interest rate'),
                    ])->columns(3),

                Forms\Components\Section::make('Duration Settings')
                    ->description('Configure loan duration options')
                    ->schema([
                        Forms\Components\TextInput::make('min_duration_months')
                            ->label('Minimum Duration')
                            ->required()
                            ->numeric()
                            ->suffix('months')
                            ->default(1),

                        Forms\Components\TextInput::make('max_duration_months')
                            ->label('Maximum Duration')
                            ->required()
                            ->numeric()
                            ->suffix('months')
                            ->default(12),

                        Forms\Components\TextInput::make('default_duration_months')
                            ->label('Default Duration')
                            ->required()
                            ->numeric()
                            ->suffix('months')
                            ->default(3),
                    ])->columns(3),

                Forms\Components\Section::make('Appearance')
                    ->description('Customize how this plan appears to users')
                    ->schema([
                        Forms\Components\Select::make('icon')
                            ->options([
                                'pi-bolt' => '⚡ Bolt (Quick/Fast)',
                                'pi-wallet' => '👛 Wallet (Standard)',
                                'pi-crown' => '👑 Crown (Premium)',
                                'pi-star' => '⭐ Star',
                                'pi-heart' => '❤️ Heart',
                                'pi-home' => '🏠 Home',
                                'pi-car' => '🚗 Car',
                                'pi-briefcase' => '💼 Briefcase',
                                'pi-graduation-cap' => '🎓 Education',
                                'pi-building' => '🏢 Business',
                            ])
                            ->default('pi-bolt'),

                        Forms\Components\Select::make('color')
                            ->options([
                                'green' => '🟢 Green',
                                'blue' => '🔵 Blue',
                                'purple' => '🟣 Purple',
                                'amber' => '🟡 Amber/Gold',
                                'red' => '🔴 Red',
                                'indigo' => '🔷 Indigo',
                                'pink' => '🩷 Pink',
                                'teal' => '🩵 Teal',
                            ])
                            ->default('green'),

                        Forms\Components\TextInput::make('gradient_from')
                            ->placeholder('e.g., green-500')
                            ->helperText('Tailwind color class'),

                        Forms\Components\TextInput::make('gradient_to')
                            ->placeholder('e.g., emerald-600')
                            ->helperText('Tailwind color class'),
                    ])->columns(4),

                Forms\Components\Section::make('Features')
                    ->description('Add features that will be displayed on the loan card')
                    ->schema([
                        Forms\Components\Repeater::make('features')
                            ->schema([
                                Forms\Components\TextInput::make('icon')
                                    ->placeholder('pi-check-circle')
                                    ->required(),
                                Forms\Components\TextInput::make('title')
                                    ->placeholder('e.g., Quick Disbursement')
                                    ->required(),
                                Forms\Components\TextInput::make('description')
                                    ->placeholder('e.g., Within 24 hours'),
                            ])
                            ->columns(3)
                            ->defaultItems(0)
                            ->addActionLabel('Add Feature')
                            ->collapsible()
                            ->itemLabel(fn (array $state): ?string => $state['title'] ?? null),
                    ]),

                Forms\Components\Section::make('Fees & Policies')
                    ->schema([
                        Forms\Components\TextInput::make('processing_fee')
                            ->numeric()
                            ->default(0)
                            ->prefix('$'),

                        Forms\Components\Select::make('processing_fee_type')
                            ->options([
                                'fixed' => 'Fixed Amount',
                                'percentage' => 'Percentage of Loan',
                            ])
                            ->default('fixed'),

                        Forms\Components\TextInput::make('approval_days')
                            ->label('Expected Approval Time')
                            ->numeric()
                            ->suffix('days')
                            ->default(1),

                        Forms\Components\TextInput::make('early_repayment_fee')
                            ->label('Early Repayment Fee')
                            ->numeric()
                            ->suffix('%')
                            ->default(0)
                            ->helperText('Percentage fee for early repayment'),
                    ])->columns(4),

                Forms\Components\Section::make('Options')
                    ->schema([
                        Forms\Components\Toggle::make('requires_collateral')
                            ->label('Requires Collateral')
                            ->default(false),

                        Forms\Components\Toggle::make('early_repayment_allowed')
                            ->label('Allow Early Repayment')
                            ->default(true),

                        Forms\Components\Toggle::make('is_featured')
                            ->label('Featured Plan')
                            ->helperText('Featured plans are highlighted to users')
                            ->default(false),

                        Forms\Components\Toggle::make('is_active')
                            ->label('Active')
                            ->helperText('Only active plans are shown to users')
                            ->default(true),
                    ])->columns(4),

                Forms\Components\TextInput::make('sort_order')
                    ->numeric()
                    ->default(0)
                    ->helperText('Lower numbers appear first'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->description(fn (LoanPlan $record) => $record->tagline),

                Tables\Columns\TextColumn::make('min_amount')
                    ->money('USD')
                    ->label('Min Amount')
                    ->sortable(),

                Tables\Columns\TextColumn::make('max_amount')
                    ->money('USD')
                    ->label('Max Amount')
                    ->sortable(),

                Tables\Columns\TextColumn::make('interest_rate')
                    ->suffix('%')
                    ->label('Interest')
                    ->sortable(),

                Tables\Columns\TextColumn::make('max_duration_months')
                    ->suffix(' months')
                    ->label('Max Term')
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_featured')
                    ->boolean()
                    ->label('Featured'),

                Tables\Columns\IconColumn::make('is_active')
                    ->boolean()
                    ->label('Active'),

                Tables\Columns\TextColumn::make('sort_order')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('loans_count')
                    ->counts('loans')
                    ->label('Applications')
                    ->sortable(),

                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active'),
                Tables\Filters\TernaryFilter::make('is_featured')
                    ->label('Featured'),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->modalWidth('5xl'),
                Tables\Actions\Action::make('toggle_active')
                    ->icon(fn (LoanPlan $record) => $record->is_active ? 'heroicon-o-eye-slash' : 'heroicon-o-eye')
                    ->label(fn (LoanPlan $record) => $record->is_active ? 'Deactivate' : 'Activate')
                    ->color(fn (LoanPlan $record) => $record->is_active ? 'warning' : 'success')
                    ->action(fn (LoanPlan $record) => $record->update(['is_active' => !$record->is_active])),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('sort_order')
            ->reorderable('sort_order');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLoanPlans::route('/'),
        ];
    }
}
