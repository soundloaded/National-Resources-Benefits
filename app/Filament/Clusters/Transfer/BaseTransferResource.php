<?php

namespace App\Filament\Clusters\Transfer;

use Filament\Resources\Resource;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Tables;
use Filament\Tables\Table;
use App\Helpers\Currency;
use App\Models\Transfer as TransferModel;
use App\Models\Transaction;
use App\Models\Account;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Mail;
use App\Mail\TransferNotification;
use Illuminate\Support\Str;

abstract class BaseTransferResource extends Resource
{
    protected static string $type;

    protected static function getType(): string
    {
        return static::$type;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Transfer Details')
                    ->schema([
                        Forms\Components\Select::make('user_id')
                            ->label('User')
                            ->relationship('user', 'name')
                            ->searchable()
                            ->required(),
                        Forms\Components\TextInput::make('amount')
                            ->label('Amount')
                            ->numeric()
                            ->prefix(Currency::getSymbol())
                            ->required(),
                        Forms\Components\Textarea::make('description')
                            ->label('Description')
                            ->rows(3)
                            ->columnSpanFull(),
                        Forms\Components\Select::make('status')
                            ->options([
                                'pending' => 'Pending',
                                'completed' => 'Completed',
                                'failed' => 'Failed',
                                'cancelled' => 'Cancelled',
                            ])
                            ->default('pending')
                            ->required(),
                    ]),
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
                Tables\Columns\TextColumn::make('amount')
                    ->money(Currency::getDefaultCode())
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->colors([
                        'warning' => 'pending',
                        'success' => 'completed',
                        'danger' => 'failed',
                        'gray' => 'cancelled',
                    ]),
                Tables\Columns\IconColumn::make('email_sent')
                    ->label('Email Sent')
                    ->boolean(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
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
                Tables\Actions\DeleteAction::make(),
            ])
            ->headerActions([
                Tables\Actions\Action::make('create_transfer')
                    ->label('New Transfer')
                    ->icon('heroicon-o-plus')
                    ->modalHeading('Create ' . ucfirst(static::getType()) . ' Transfer')
                    ->modalWidth('md')
                    ->form([
                        Forms\Components\Select::make('user_id')
                            ->label('User')
                            ->options(User::orderBy('name')->pluck('name', 'id')->toArray())
                            ->searchable()
                            ->required(),
                        Forms\Components\TextInput::make('amount')
                            ->label('Amount')
                            ->numeric()
                            ->prefix(Currency::getSymbol())
                            ->required(),
                        Forms\Components\Textarea::make('description')
                            ->label('Description')
                            ->rows(3)
                            ->columnSpanFull(),
                        Forms\Components\Select::make('status')
                            ->options([
                                'pending' => 'Pending',
                                'completed' => 'Completed',
                                'failed' => 'Failed',
                                'cancelled' => 'Cancelled',
                            ])
                            ->default('completed')
                            ->required(),
                        Forms\Components\Checkbox::make('send_email')
                            ->label('Send Email Notification to User')
                            ->default(false),
                    ])
                    ->action(function (array $data) {
                        $transfer = TransferModel::create([
                            'user_id' => $data['user_id'],
                            'type' => static::getType(),
                            'amount' => $data['amount'],
                            'description' => $data['description'] ?? null,
                            'status' => $data['status'],
                            'email_sent' => false,
                            'created_by' => auth()->id(),
                        ]);

                        // Create a corresponding transaction record
                        $account = Account::where('user_id', $data['user_id'])->first();
                        if ($account) {
                            Transaction::create([
                                'account_id' => $account->id,
                                'transaction_type' => 'transfer_out',
                                'method' => static::getType() . '_transfer',
                                'amount' => $data['amount'],
                                'currency' => 'USD',
                                'status' => $data['status'],
                                'description' => $data['description'] ?? ucfirst(static::getType()) . ' transfer',
                                'reference_number' => 'TRF-' . strtoupper(Str::random(12)),
                                'metadata' => [
                                    'transfer_id' => $transfer->id,
                                    'transfer_type' => static::getType(),
                                    'created_by' => auth()->id(),
                                ],
                                'completed_at' => $data['status'] === 'completed' ? now() : null,
                            ]);
                        }

                        if (!empty($data['send_email'])) {
                            $user = User::find($data['user_id']);
                            Mail::to($user->email)->send(new TransferNotification($transfer));
                            $transfer->update(['email_sent' => true]);
                        }

                        \Filament\Notifications\Notification::make()
                            ->title(ucfirst(static::getType()) . ' transfer created')
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

    protected static function getTableQuery(): Builder
    {
        return TransferModel::query()
            ->where('type', static::getType());
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('type', static::getType());
    }
}
