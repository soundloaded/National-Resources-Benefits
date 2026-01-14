<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TransactionResource\Pages;
use App\Filament\Resources\TransactionResource\RelationManagers;
use App\Models\Transaction;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class TransactionResource extends Resource
{
    protected static ?string $model = Transaction::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';
    
    protected static ?string $navigationGroup = 'Financial Management';
    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Transaction Details')
                    ->schema([
                        Forms\Components\Select::make('account_id')
                            ->relationship('account', 'account_number')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->label('Account'),
                        Forms\Components\TextInput::make('reference_number')
                            ->default(fn () => 'TRX-' . strtoupper(uniqid()))
                            ->required()
                            ->maxLength(255),
                        Forms\Components\Select::make('transaction_type')
                            ->options([
                                'deposit' => 'Deposit',
                                'withdrawal' => 'Withdrawal',
                                'transfer_in' => 'Transfer In',
                                'transfer_out' => 'Transfer Out',
                                'loan' => 'Loan Disbursement',
                                'loan_repayment' => 'Loan Repayment',
                                'referral_reward' => 'Referral Reward',
                                'rank_reward' => 'Rank Achievement Bonus',
                                'funding_disbursement' => 'Funding Disbursement',
                                'grant' => 'Grant Disbursement',
                            ])
                            ->live()
                            ->required()
                            ->native(false),
                        Forms\Components\Select::make('method')
                            ->label('Method')
                            ->options(fn (Get $get) => match ($get('transaction_type')) {
                                'deposit' => [
                                    'ach' => 'ACH Deposit',
                                    'wire' => 'Wire Transfer',
                                    'check' => 'Mobile Check Deposit',
                                    'direct_deposit' => 'Direct Deposit',
                                ],
                                'withdrawal' => [
                                    'ach' => 'ACH Withdrawal',
                                    'wire' => 'Wire Withdrawal',
                                    'check' => 'Check by Mail',
                                ],
                                'transfer_in', 'transfer_out' => [
                                    'domestic' => 'Domestic Transfer',
                                    'wire' => 'Wire Transfer',
                                    'p2p' => 'User-to-User',
                                    'internal' => 'Account-to-Account',
                                ],
                                'loan', 'loan_repayment' => [
                                    'internal' => 'Internal Credit',
                                    'external' => 'External Funding',
                                ],
                                'referral_reward', 'rank_reward' => [
                                    'automatic' => 'Automatic Reward',
                                    'manual' => 'Manual Credit',
                                ],
                                'funding_disbursement', 'grant' => [
                                    'platform' => 'Platform Disbursement',
                                    'manual' => 'Manual Credit',
                                ],
                                default => [],
                            })
                            ->required(fn (Get $get) => $get('transaction_type') !== null)
                            ->native(false),
                        Forms\Components\TextInput::make('amount')
                            ->required()
                            ->numeric()
                            ->prefix('$')
                            ->maxValue(9999999999.99),
                        Forms\Components\TextInput::make('currency')
                            ->default('USD')
                            ->required()
                            ->maxLength(3),
                        Forms\Components\Select::make('status')
                            ->options([
                                'pending' => 'Pending',
                                'completed' => 'Completed',
                                'failed' => 'Failed',
                                'cancelled' => 'Cancelled',
                            ])
                            ->default('pending')
                            ->required()
                            ->native(false),
                        Forms\Components\DateTimePicker::make('completed_at')
                            ->label('Completion Date'),
                        Forms\Components\Textarea::make('description')
                            ->maxLength(65535)
                            ->columnSpanFull(),
                        
                        Forms\Components\Section::make('Loan Details')
                            ->schema([
                                Forms\Components\TextInput::make('metadata.interest_rate')
                                    ->label('Interest Rate')
                                    ->suffix('%')
                                    ->numeric(),
                                Forms\Components\TextInput::make('metadata.term_months')
                                    ->label('Term (Months)')
                                    ->numeric(),
                                Forms\Components\DatePicker::make('metadata.payment_due_date')
                                    ->label('Next Payment Due'),
                            ])
                            ->visible(fn (Get $get) => in_array($get('transaction_type'), ['loan']))
                            ->columns(2),
                    ])->columns(2),
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Components\Section::make('Transaction Information')
                    ->schema([
                        Components\Grid::make(3)
                            ->schema([
                                Components\TextEntry::make('reference_number')
                                    ->label('Reference')
                                    ->copyable()
                                    ->weight('bold')
                                    ->fontFamily('mono'),
                                Components\TextEntry::make('status')
                                    ->badge()
                                    ->colors([
                                        'success' => 'completed',
                                        'warning' => 'pending',
                                        'danger' => ['failed', 'cancelled'],
                                    ])
                                    ->icon(fn (string $state): string => match ($state) {
                                        'completed' => 'heroicon-m-check-circle',
                                        'pending' => 'heroicon-m-clock',
                                        'failed' => 'heroicon-m-x-circle',
                                        'cancelled' => 'heroicon-m-x-circle',
                                        default => 'heroicon-m-question-mark-circle',
                                    }),
                                Components\TextEntry::make('transaction_type')
                                    ->badge()
                                    ->colors([
                                        'success' => fn ($state) => in_array($state, ['deposit', 'transfer_in', 'referral_reward', 'rank_reward', 'funding_disbursement', 'grant']),
                                        'danger' => fn ($state) => in_array($state, ['withdrawal', 'transfer_out']),
                                        'info' => fn ($state) => in_array($state, ['loan', 'loan_repayment']),
                                    ])
                                    ->formatStateUsing(fn (string $state): string => str($state)->headline()),
                                Components\TextEntry::make('method')
                                    ->label('Method')
                                    ->badge()
                                    ->formatStateUsing(fn ($state) => str($state)->headline()),
                                Components\TextEntry::make('amount')
                                    ->money(fn ($record) => $record->currency)
                                    ->weight('bold')
                                    ->size('lg'),
                                Components\TextEntry::make('currency')
                                    ->label('Currency'),
                                Components\TextEntry::make('completed_at')
                                    ->dateTime()
                                    ->placeholder('Not completed'),
                            ]),
                        Components\TextEntry::make('description')
                            ->placeholder('No description provided')
                            ->columnSpanFull(),
                    ]),
                Components\Section::make('Related Information')
                    ->schema([
                        Components\Grid::make(2)
                            ->schema([
                                Components\TextEntry::make('account.account_number')
                                    ->label('Account Number')
                                    ->icon('heroicon-m-credit-card'),
                                Components\TextEntry::make('account.user.name')
                                    ->label('User Name')
                                    ->icon('heroicon-m-user'),
                                Components\TextEntry::make('account.user.email')
                                    ->label('User Email')
                                    ->icon('heroicon-m-envelope')
                                    ->copyable(),
                            ]),
                    ]),
                Components\Section::make('Metadata')
                    ->schema([
                        Components\KeyValueEntry::make('metadata'),
                    ])
                    ->collapsible()
                    ->collapsed(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('reference_number')
                    ->searchable()
                    ->copyable()
                    ->weight('bold')
                    ->fontFamily('mono'),
                Tables\Columns\TextColumn::make('account.account_number')
                    ->label('Account')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('account.user.name')
                    ->label('User')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('transaction_type')
                    ->badge()
                    ->colors([
                        'success' => fn ($state) => in_array($state, ['deposit', 'transfer_in', 'referral_reward', 'rank_reward', 'funding_disbursement', 'grant']),
                        'danger' => fn ($state) => in_array($state, ['withdrawal', 'transfer_out']),
                        'info' => fn ($state) => in_array($state, ['loan', 'loan_repayment']),
                    ])
                    ->formatStateUsing(fn (string $state): string => str($state)->headline()),
                Tables\Columns\TextColumn::make('method')
                    ->formatStateUsing(fn (string $state): string => str($state)->headline())
                    ->toggleable(),
                Tables\Columns\TextColumn::make('amount')
                    ->money(fn ($record) => $record->currency)
                    ->sortable()
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->colors([
                        'success' => 'completed',
                        'warning' => 'pending',
                        'danger' => ['failed', 'cancelled'],
                    ])
                    ->icon(fn (string $state): string => match ($state) {
                        'completed' => 'heroicon-m-check-circle',
                        'pending' => 'heroicon-m-clock',
                        'failed' => 'heroicon-m-x-circle',
                        'cancelled' => 'heroicon-m-x-circle',
                        default => 'heroicon-m-question-mark-circle',
                    }),
                Tables\Columns\TextColumn::make('completed_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('transaction_type')
                    ->options([
                        'deposit' => 'Deposit',
                        'withdrawal' => 'Withdrawal',
                        'transfer_in' => 'Transfer In',
                        'transfer_out' => 'Transfer Out',
                        'loan' => 'Loan Disbursement',
                        'loan_repayment' => 'Loan Repayment',
                        'referral_reward' => 'Referral Reward',
                        'rank_reward' => 'Rank Achievement Bonus',
                        'funding_disbursement' => 'Funding Disbursement',
                        'grant' => 'Grant Disbursement',
                    ]),
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'completed' => 'Completed',
                        'failed' => 'Failed',
                        'cancelled' => 'Cancelled',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\ViewAction::make(),
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
            'index' => Pages\ListTransactions::route('/'),
            'create' => Pages\CreateTransaction::route('/create'),
            'view' => Pages\ViewTransaction::route('/{record}'),
            'edit' => Pages\EditTransaction::route('/{record}/edit'),
        ];
    }
}
