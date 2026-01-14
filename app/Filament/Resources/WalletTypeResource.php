<?php

namespace App\Filament\Resources;

use App\Filament\Resources\WalletTypeResource\Pages;
use App\Filament\Resources\WalletTypeResource\RelationManagers;
use App\Models\WalletType;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class WalletTypeResource extends Resource
{
    protected static ?string $model = WalletType::class;

    protected static ?string $navigationIcon = 'heroicon-o-wallet';
    protected static ?string $navigationGroup = 'Financial Management';
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Wallet Configuration')
                    ->schema([
                        Forms\Components\Grid::make(2)->schema([
                            Forms\Components\TextInput::make('name')
                                ->required()
                                ->maxLength(255)
                                ->placeholder('e.g., Premium Savings'),
                            Forms\Components\TextInput::make('slug')
                                ->required()
                                ->unique(ignoreRecord: true)
                                ->maxLength(255)
                                ->placeholder('e.g., premium-savings'),
                        ]),
                        Forms\Components\Grid::make(3)->schema([
                            Forms\Components\TextInput::make('currency_code')
                                ->label('Currency Code')
                                ->required()
                                ->maxLength(10)
                                ->default('USD'),
                            Forms\Components\Select::make('type')
                                ->options([
                                    'fiat' => 'Fiat Currency',
                                    'crypto' => 'Cryptocurrency',
                                ])
                                ->required()
                                ->default('fiat'),
                            Forms\Components\TextInput::make('display_order')
                                ->numeric()
                                ->default(0),
                        ]),
                        Forms\Components\Toggle::make('is_default')
                            ->label('Primary Wallet')
                            ->helperText('If enabled, this wallet will be created automatically for new users.')
                            ->columnSpanFull(),
                        Forms\Components\Toggle::make('is_active')
                            ->default(true)
                            ->columnSpanFull(),
                    ])
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('currency_code')->badge(),
                Tables\Columns\TextColumn::make('type')->badge()->color(fn (string $state): string => match ($state) {
                    'fiat' => 'success',
                    'crypto' => 'info',
                }),
                Tables\Columns\IconColumn::make('is_default')
                    ->label('Primary')
                    ->boolean(),
                Tables\Columns\IconColumn::make('is_active')
                    ->boolean(),
                Tables\Columns\TextColumn::make('accounts_count')
                    ->counts('accounts')
                    ->label('Users'),
            ])
            ->filters([
                //
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
            'index' => Pages\ListWalletTypes::route('/'),
            'create' => Pages\CreateWalletType::route('/create'),
            'edit' => Pages\EditWalletType::route('/{record}/edit'),
        ];
    }
}
