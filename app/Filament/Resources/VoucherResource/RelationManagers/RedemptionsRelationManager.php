<?php

namespace App\Filament\Resources\VoucherResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class RedemptionsRelationManager extends RelationManager
{
    protected static string $relationship = 'redemptions';

    protected static ?string $title = 'Redemption History';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('user_id')
                    ->relationship('user', 'name')
                    ->searchable()
                    ->required(),
                Forms\Components\TextInput::make('amount_redeemed')
                    ->numeric()
                    ->prefix('$')
                    ->required(),
                Forms\Components\Select::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'completed' => 'Completed',
                        'reversed' => 'Reversed',
                    ])
                    ->default('completed')
                    ->required(),
                Forms\Components\Textarea::make('notes')
                    ->rows(2),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->label('User')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('user.email')
                    ->label('Email')
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('amount_redeemed')
                    ->money('USD')
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->colors([
                        'warning' => 'pending',
                        'success' => 'completed',
                        'danger' => 'reversed',
                    ]),
                Tables\Columns\TextColumn::make('redeemed_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'completed' => 'Completed',
                        'reversed' => 'Reversed',
                    ]),
            ])
            ->headerActions([
                // Manual redemption by admin
                Tables\Actions\CreateAction::make()
                    ->label('Add Manual Redemption')
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['redeemed_at'] = now();
                        return $data;
                    })
                    ->after(function ($record) {
                        // Increment voucher usage
                        $record->voucher->increment('current_uses');
                    }),
            ])
            ->actions([
                Tables\Actions\Action::make('reverse')
                    ->label('Reverse')
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn ($record) => $record->status === 'completed')
                    ->action(function ($record) {
                        $record->update(['status' => 'reversed']);
                        $record->voucher->decrement('current_uses');
                    }),
            ])
            ->bulkActions([])
            ->defaultSort('redeemed_at', 'desc');
    }
}
