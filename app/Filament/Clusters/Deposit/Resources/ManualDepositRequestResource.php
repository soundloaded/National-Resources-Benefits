<?php

namespace App\Filament\Clusters\Deposit\Resources;

use App\Filament\Clusters\Deposit;
use App\Filament\Clusters\Deposit\Resources\ManualDepositRequestResource\Pages;
use App\Models\Transaction;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ManualDepositRequestResource extends Resource
{
    protected static ?string $model = Transaction::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-check';

    protected static ?string $cluster = Deposit::class;

    protected static ?string $navigationLabel = 'Manual Requests';

    protected static ?int $navigationSort = 1;


    public static function form(Form $form): Form
    {
        return $form
             ->schema([
                Forms\Components\Section::make('Review Deposit')
                    ->schema([
                        Forms\Components\Select::make('status')
                            ->options([
                                'pending' => 'Pending',
                                'completed' => 'Approved (Completed)',
                                'failed' => 'Rejected (Failed)',
                            ])
                            ->required(),
                        Forms\Components\Textarea::make('description')
                            ->label('Admin Notes'),
                    ])
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('reference_number')->searchable(),
                Tables\Columns\TextColumn::make('account.user.name')->label('User'),
                Tables\Columns\TextColumn::make('amount')->money('USD'),
                Tables\Columns\TextColumn::make('method')->badge(),
                Tables\Columns\TextColumn::make('created_at')->dateTime()->label('Requested At'),
            ])
            ->actions([
                Tables\Actions\EditAction::make()->label('Review'),
            ])
            ->modifyQueryUsing(fn (Builder $query) => $query
                ->where('transaction_type', 'deposit')
                ->where('status', 'pending')
                ->whereIn('method', fn ($q) => $q->select('code')->from('payment_gateways')->where('type', 'manual'))
            );
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageManualDepositRequests::route('/'),
        ];
    }
}
