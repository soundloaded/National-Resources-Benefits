<?php

namespace App\Filament\Resources;

use App\Filament\Resources\VoucherResource\Pages;
use App\Models\Voucher;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class VoucherResource extends Resource
{
    protected static ?string $model = Voucher::class;

    protected static ?string $navigationIcon = 'heroicon-o-ticket';
    protected static ?string $navigationGroup = 'Content Management';
    protected static ?string $navigationLabel = 'Vouchers';
    protected static ?int $navigationSort = 5;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Voucher Details')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('code')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(50)
                            ->default(fn () => strtoupper(Str::random(10)))
                            ->suffixAction(
                                Forms\Components\Actions\Action::make('generate')
                                    ->icon('heroicon-o-arrow-path')
                                    ->action(fn ($set) => $set('code', strtoupper(Str::random(10))))
                            ),

                        Forms\Components\Textarea::make('description')
                            ->rows(3)
                            ->columnSpanFull(),

                        Forms\Components\TextInput::make('value')
                            ->required()
                            ->numeric()
                            ->prefix('$')
                            ->minValue(0.01)
                            ->step(0.01),

                        Forms\Components\Select::make('category')
                            ->options([
                                'housing' => 'Housing',
                                'food' => 'Food',
                                'healthcare' => 'Healthcare',
                                'education' => 'Education',
                                'transportation' => 'Transportation',
                                'utilities' => 'Utilities',
                                'general' => 'General',
                            ])
                            ->default('general')
                            ->required(),
                    ])->columns(2),

                Forms\Components\Section::make('Usage Settings')
                    ->schema([
                        Forms\Components\Select::make('voucher_type')
                            ->options([
                                'single_use' => 'Single Use (One redemption only)',
                                'multi_use' => 'Multi Use (Multiple redemptions)',
                            ])
                            ->default('single_use')
                            ->required()
                            ->live(),

                        Forms\Components\TextInput::make('max_uses')
                            ->label('Maximum Uses')
                            ->numeric()
                            ->minValue(1)
                            ->helperText('Leave empty for unlimited uses')
                            ->visible(fn ($get) => $get('voucher_type') === 'multi_use'),

                        Forms\Components\TextInput::make('current_uses')
                            ->label('Current Uses')
                            ->numeric()
                            ->default(0)
                            ->disabled()
                            ->dehydrated(false)
                            ->visibleOn('edit'),
                    ])->columns(3),

                Forms\Components\Section::make('Validity Period')
                    ->schema([
                        Forms\Components\DateTimePicker::make('start_date')
                            ->label('Start Date')
                            ->helperText('Leave empty to start immediately'),

                        Forms\Components\DateTimePicker::make('expiration_date')
                            ->label('Expiration Date')
                            ->helperText('Leave empty for no expiration')
                            ->after('start_date'),

                        Forms\Components\Toggle::make('is_active')
                            ->label('Active')
                            ->default(true)
                            ->helperText('Inactive vouchers cannot be redeemed'),
                    ])->columns(3),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('code')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->copyMessage('Voucher code copied')
                    ->weight('bold')
                    ->color('primary'),

                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->limit(30),

                Tables\Columns\TextColumn::make('value')
                    ->money('USD')
                    ->sortable(),

                Tables\Columns\TextColumn::make('category')
                    ->badge()
                    ->colors([
                        'success' => 'housing',
                        'warning' => 'food',
                        'danger' => 'healthcare',
                        'info' => 'education',
                        'primary' => 'transportation',
                        'secondary' => 'utilities',
                        'gray' => 'general',
                    ]),

                Tables\Columns\TextColumn::make('voucher_type')
                    ->label('Type')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state === 'single_use' ? 'Single' : 'Multi')
                    ->colors([
                        'info' => 'single_use',
                        'success' => 'multi_use',
                    ]),

                Tables\Columns\TextColumn::make('current_uses')
                    ->label('Uses')
                    ->formatStateUsing(function ($record) {
                        if ($record->voucher_type === 'single_use') {
                            return $record->current_uses . '/1';
                        }
                        return $record->max_uses 
                            ? $record->current_uses . '/' . $record->max_uses 
                            : $record->current_uses . '/∞';
                    })
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->colors([
                        'success' => 'active',
                        'warning' => 'scheduled',
                        'danger' => 'expired',
                        'gray' => fn ($state) => in_array($state, ['inactive', 'used', 'exhausted']),
                    ]),

                Tables\Columns\TextColumn::make('expiration_date')
                    ->label('Expires')
                    ->dateTime('M d, Y')
                    ->sortable()
                    ->placeholder('Never'),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('category')
                    ->options([
                        'housing' => 'Housing',
                        'food' => 'Food',
                        'healthcare' => 'Healthcare',
                        'education' => 'Education',
                        'transportation' => 'Transportation',
                        'utilities' => 'Utilities',
                        'general' => 'General',
                    ]),

                Tables\Filters\SelectFilter::make('voucher_type')
                    ->label('Type')
                    ->options([
                        'single_use' => 'Single Use',
                        'multi_use' => 'Multi Use',
                    ]),

                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active Status'),

                Tables\Filters\Filter::make('expired')
                    ->label('Show Expired')
                    ->query(fn ($query) => $query->where('expiration_date', '<', now()))
                    ->toggle(),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\Action::make('copy_code')
                        ->label('Copy Code')
                        ->icon('heroicon-o-clipboard')
                        ->action(fn () => null) // JS handles copy via copyable column
                        ->color('gray'),
                    Tables\Actions\Action::make('toggle_active')
                        ->label(fn ($record) => $record->is_active ? 'Deactivate' : 'Activate')
                        ->icon(fn ($record) => $record->is_active ? 'heroicon-o-x-circle' : 'heroicon-o-check-circle')
                        ->color(fn ($record) => $record->is_active ? 'danger' : 'success')
                        ->requiresConfirmation()
                        ->action(fn ($record) => $record->update(['is_active' => !$record->is_active])),
                    Tables\Actions\DeleteAction::make(),
                ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\BulkAction::make('activate')
                        ->label('Activate Selected')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->action(fn ($records) => $records->each->update(['is_active' => true]))
                        ->deselectRecordsAfterCompletion(),
                    Tables\Actions\BulkAction::make('deactivate')
                        ->label('Deactivate Selected')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->action(fn ($records) => $records->each->update(['is_active' => false]))
                        ->deselectRecordsAfterCompletion(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            VoucherResource\RelationManagers\RedemptionsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListVouchers::route('/'),
            'create' => Pages\CreateVoucher::route('/create'),
            'view' => Pages\ViewVoucher::route('/{record}'),
            'edit' => Pages\EditVoucher::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('expiration_date')
                    ->orWhere('expiration_date', '>', now());
            })
            ->count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'success';
    }
}
