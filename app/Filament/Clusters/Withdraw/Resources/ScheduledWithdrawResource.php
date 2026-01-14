<?php

namespace App\Filament\Clusters\Withdraw\Resources;

use App\Filament\Clusters\Withdraw;
use App\Filament\Clusters\Withdraw\Resources\ScheduledWithdrawResource\Pages;
use App\Models\Transaction;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ScheduledWithdrawResource extends Resource
{
    protected static ?string $model = Transaction::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar';

    protected static ?string $cluster = Withdraw::class;

    protected static ?string $navigationLabel = 'Scheduled Withdraws';

    protected static ?int $navigationSort = 4;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('reference_number')->readOnly(),
                Forms\Components\TextInput::make('amount')->prefix('$')->readOnly(),
                Forms\Components\TextInput::make('method')->readOnly(),
                Forms\Components\TextInput::make('status')->readOnly(),
                Forms\Components\DatePicker::make('metadata.scheduled_date')->label('Scheduled Date')->readOnly(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('reference_number'),
                Tables\Columns\TextColumn::make('account.account_number'),
                Tables\Columns\TextColumn::make('amount')->money('USD'),
                Tables\Columns\TextColumn::make('method')->badge(),
                Tables\Columns\TextColumn::make('metadata.scheduled_date')->date()->label('Scheduled For'),
                Tables\Columns\TextColumn::make('status')->badge(),
            ])
            ->modifyQueryUsing(fn (Builder $query) => $query
                ->where('transaction_type', 'withdrawal')
                ->where('status', 'scheduled')
            );
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListScheduledWithdraws::route('/'),
        ];
    }
}
