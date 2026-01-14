<?php

namespace App\Filament\Clusters\Withdraw\Resources;

use App\Filament\Clusters\Withdraw;
use App\Filament\Clusters\Withdraw\Resources\LinkedWithdrawalAccountResource\Pages;
use App\Models\LinkedWithdrawalAccount;
use App\Models\WithdrawalFormField;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class LinkedWithdrawalAccountResource extends Resource
{
    protected static ?string $model = LinkedWithdrawalAccount::class;

    protected static ?string $navigationIcon = 'heroicon-o-link';

    protected static ?string $cluster = Withdraw::class;

    protected static ?string $navigationLabel = 'Linked Accounts';

    protected static ?string $modelLabel = 'Linked Account';

    protected static ?string $pluralModelLabel = 'Linked Accounts';

    protected static ?int $navigationSort = 11;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Account Information')
                    ->schema([
                        Forms\Components\Select::make('user_id')
                            ->relationship('user', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                        Forms\Components\TextInput::make('account_name')
                            ->required()
                            ->maxLength(255)
                            ->label('Account Nickname'),
                    ])->columns(2),

                Forms\Components\Section::make('Account Details')
                    ->schema([
                        Forms\Components\KeyValue::make('account_data')
                            ->label('Account Data')
                            ->keyLabel('Field')
                            ->valueLabel('Value')
                            ->reorderable(),
                    ]),

                Forms\Components\Section::make('Status')
                    ->schema([
                        Forms\Components\Toggle::make('is_default')
                            ->label('Default Account'),
                        Forms\Components\Toggle::make('is_verified')
                            ->label('Verified')
                            ->reactive(),
                        Forms\Components\Toggle::make('is_active')
                            ->label('Active')
                            ->default(true),
                        Forms\Components\DateTimePicker::make('verified_at')
                            ->label('Verified At')
                            ->visible(fn (Forms\Get $get): bool => $get('is_verified')),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->label('User')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('account_name')
                    ->label('Account Name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('account_data')
                    ->label('Details')
                    ->formatStateUsing(function ($state) {
                        if (!$state) return '-';
                        $data = is_array($state) ? $state : json_decode($state, true);
                        if (!is_array($data) || empty($data)) return '-';
                        $display = [];
                        foreach ($data as $key => $value) {
                            if (in_array($key, ['account_number', 'routing_number'])) {
                                $value = '****' . substr((string)$value, -4);
                            }
                            $display[] = ucwords(str_replace('_', ' ', $key)) . ': ' . $value;
                        }
                        return implode(' | ', array_slice($display, 0, 3));
                    })
                    ->wrap()
                    ->limit(60),
                Tables\Columns\IconColumn::make('is_default')
                    ->boolean()
                    ->label('Default'),
                Tables\Columns\IconColumn::make('is_verified')
                    ->boolean()
                    ->label('Verified'),
                Tables\Columns\IconColumn::make('is_active')
                    ->boolean()
                    ->label('Active'),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_verified')
                    ->label('Verification Status'),
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active Status'),
            ])
            ->actions([
                Tables\Actions\Action::make('verify')
                    ->icon('heroicon-o-check-badge')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (LinkedWithdrawalAccount $record): bool => !$record->is_verified)
                    ->action(function (LinkedWithdrawalAccount $record): void {
                        $record->update([
                            'is_verified' => true,
                            'verified_at' => now(),
                        ]);
                    }),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\BulkAction::make('verify')
                        ->label('Verify Selected')
                        ->icon('heroicon-o-check-badge')
                        ->requiresConfirmation()
                        ->action(function ($records): void {
                            $records->each(function ($record) {
                                $record->update([
                                    'is_verified' => true,
                                    'verified_at' => now(),
                                ]);
                            });
                        }),
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
            'index' => Pages\ListLinkedWithdrawalAccounts::route('/'),
            'create' => Pages\CreateLinkedWithdrawalAccount::route('/create'),
            'edit' => Pages\EditLinkedWithdrawalAccount::route('/{record}/edit'),
        ];
    }
}
