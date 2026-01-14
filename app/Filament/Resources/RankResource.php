<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RankResource\Pages;
use App\Models\Rank;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class RankResource extends Resource
{
    protected static ?string $model = Rank::class;

    protected static ?string $navigationIcon = 'heroicon-o-trophy';
    
    protected static ?string $navigationGroup = 'User Management';
    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make()
                    ->schema([
                        Forms\Components\FileUpload::make('icon')
                            ->image()
                            ->avatar()
                            ->imageEditor()
                            ->circleCropper()
                            ->directory('ranks')
                            ->columnSpanFull()
                            ->alignCenter(),
                        
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('Enter rank name'),

                        Forms\Components\TextInput::make('description')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('Enter rank description'),

                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('min_transaction_volume')
                                    ->label('Transaction Amount')
                                    ->numeric()
                                    ->prefix('USD')
                                    ->default(0)
                                    ->required(),

                                Forms\Components\TextInput::make('reward')
                                    ->label('Rank Reward')
                                    ->numeric()
                                    ->prefix('USD')
                                    ->default(0)
                                    ->required(),
                            ]),

                        Forms\Components\CheckboxList::make('allowed_transaction_types')
                            ->label('Allow Transaction Type')
                            ->options([
                                'deposit' => 'Deposit',
                                'withdrawal' => 'Withdrawal',
                                'transfer' => 'Transfer (Send Money)',
                                'loan' => 'Loan Request',
                                'payment' => 'Payment',
                                'referral_reward' => 'Referral Reward',
                                'rank_reward' => 'Rank Reward',
                            ])
                            ->columns(2)
                            ->gridDirection('row')
                            ->columnSpanFull(),

                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('max_wallets')
                                    ->label('Max Wallet Create')
                                    ->numeric()
                                    ->suffix('Wallet')
                                    ->default(1)
                                    ->required(),

                                Forms\Components\TextInput::make('max_referral_level')
                                    ->label('Max Referral Level')
                                    ->numeric()
                                    ->suffix('Level')
                                    ->default(1)
                                    ->required(),
                            ]),

                        Forms\Components\Toggle::make('is_active')
                            ->label('Active')
                            ->default(true),
                        
                        Forms\Components\Toggle::make('is_default')
                            ->label('Default Rank')
                            ->helperText('Set this as the starting rank for new users')
                            ->default(false),
                    ])
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('icon')
                    ->circular(),
                
                Tables\Columns\TextColumn::make('name')
                    ->description(fn (Rank $record): string => \Illuminate\Support\Str::limit($record->description, 50))
                    ->searchable()
                    ->weight('bold'),
                
                Tables\Columns\TextColumn::make('min_transaction_volume')
                    ->money('USD')
                    ->label('Trx Amount')
                    ->description(fn (Rank $record): string => 'Reward: ' . \NumberFormatter::create('en_US', \NumberFormatter::CURRENCY)->formatCurrency($record->reward, 'USD')),

                Tables\Columns\TextColumn::make('allowed_transaction_types')
                    ->label('Trx Type Allowed')
                    ->badge()
                    ->separator(',')
                    ->color(fn (string $state): string => match ($state) {
                        'deposit' => 'info',
                        'referral_reward' => 'success',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('is_active')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (bool $state) => $state ? 'ACTIVE' : 'INACTIVE')
                    ->color(fn (bool $state) => $state ? 'success' : 'danger'),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make()->label('Manage')->modalWidth('md'),
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
            'index' => Pages\ListRanks::route('/'),
            'create' => Pages\CreateRank::route('/create'),
            'edit' => Pages\EditRank::route('/{record}/edit'),
        ];
    }
}
