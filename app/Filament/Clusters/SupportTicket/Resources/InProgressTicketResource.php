<?php

namespace App\Filament\Clusters\SupportTicket\Resources;

use App\Filament\Clusters\SupportTicket;
use App\Filament\Clusters\SupportTicket\Resources\InProgressTicketResource\Pages;
use App\Filament\Clusters\SupportTicket\Resources\InProgressTicketResource\RelationManagers;
use App\Models\SupportTicket as SupportTicketModel;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class InProgressTicketResource extends Resource
{
    protected static ?string $model = SupportTicketModel::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-path';
    protected static ?string $navigationLabel = 'In Progress Ticket';
    protected static ?int $navigationSort = 2;

    protected static ?string $cluster = SupportTicket::class;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('status', 'in_progress');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('user_id')
                    ->relationship('user', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),
                Forms\Components\TextInput::make('subject')
                    ->required()
                    ->maxLength(255),
                Forms\Components\Select::make('category')
                    ->options(\App\Models\SupportCategory::where('is_active', true)->pluck('name', 'name'))
                    ->required(),
                Forms\Components\Select::make('priority')
                    ->options([
                        'low' => 'Low',
                        'medium' => 'Medium',
                        'high' => 'High',
                    ])
                    ->required()
                    ->default('medium'),
                Forms\Components\Select::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'in_progress' => 'In Progress',
                        'closed' => 'Closed',
                    ])
                    ->required()
                    ->default('pending'),
                Forms\Components\Textarea::make('message')
                    ->required()
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('subject')
                    ->label('Title | User')
                    ->description(fn (SupportTicketModel $record) => $record->user->name ?? 'Unknown')
                    ->searchable()
                    ->wrap(),
                Tables\Columns\TextColumn::make('ticket_id')
                    ->label('Ticket ID | Priority')
                    ->description(fn (SupportTicketModel $record) => ucfirst($record->priority))
                    ->searchable(),
                Tables\Columns\TextColumn::make('category')
                    ->searchable()
                    ->badge()
                    ->color('gray'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'in_progress' => 'info',
                        'closed' => 'success',
                        default => 'gray',
                    }),
            ])
            ->filters([
                //
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
            'index' => Pages\ListInProgressTickets::route('/'),
        ];
    }
}
