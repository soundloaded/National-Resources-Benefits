<?php

namespace App\Filament\Clusters\Deposit\Resources;

use App\Filament\Clusters\Deposit;
use App\Filament\Clusters\Deposit\Resources\DepositHistoryResource\Pages;
use App\Models\Transaction;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class DepositHistoryResource extends Resource
{
    protected static ?string $model = Transaction::class;

    protected static ?string $navigationIcon = 'heroicon-o-clock';

    protected static ?string $cluster = Deposit::class;
    
    protected static ?string $navigationLabel = 'Deposit History';

    protected static ?int $navigationSort = 4;


    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Deposit Details')
                    ->schema([
                        Forms\Components\Select::make('account_id')
                            ->relationship('account', 'account_number')
                            ->disabled(),
                        Forms\Components\TextInput::make('amount')
                            ->numeric()
                            ->prefix('$')
                            ->disabled(),
                        Forms\Components\TextInput::make('currency')
                            ->default('USD')
                            ->disabled(),
                        Forms\Components\TextInput::make('method')
                            ->formatStateUsing(fn (string $state): string => strtoupper($state))
                            ->disabled(),
                        Forms\Components\TextInput::make('reference_number')
                            ->disabled(),
                        Forms\Components\Select::make('status')
                            ->options([
                                'pending' => 'Pending',
                                'completed' => 'Completed',
                                'failed' => 'Failed',
                            ])
                            ->disabled(),
                        Forms\Components\Textarea::make('description')
                            ->disabled()
                            ->columnSpanFull(),
                    ])->columns(2)
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('reference_number')->searchable(),
                Tables\Columns\TextColumn::make('account.user.name')
                    ->label('User')
                    ->searchable(),
                Tables\Columns\TextColumn::make('amount')->money('USD'),
                Tables\Columns\TextColumn::make('method')
                    ->formatStateUsing(fn (string $state): string => strtoupper($state))
                    ->badge(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->colors([
                        'success' => 'completed',
                        'warning' => 'pending',
                        'danger' => 'failed',
                    ]),
                Tables\Columns\TextColumn::make('created_at')->dateTime()->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'completed' => 'Completed',
                        'pending' => 'Pending',
                        'failed' => 'Failed',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->modifyQueryUsing(fn (Builder $query) => $query->where('transaction_type', 'deposit'));
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDepositHistory::route('/'),
        ];
    }
}
