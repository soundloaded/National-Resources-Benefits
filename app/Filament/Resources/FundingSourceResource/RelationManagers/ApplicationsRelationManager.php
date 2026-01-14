<?php

namespace App\Filament\Resources\FundingSourceResource\RelationManagers;

use App\Models\FundingApplication;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class ApplicationsRelationManager extends RelationManager
{
    protected static string $relationship = 'applications';
    protected static ?string $title = 'Applications';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('application_number')
            ->columns([
                Tables\Columns\TextColumn::make('application_number')
                    ->label('App #')
                    ->searchable(),
                
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Applicant')
                    ->searchable(),
                
                Tables\Columns\TextColumn::make('requested_amount')
                    ->money('USD'),
                
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'under_review' => 'info',
                        'approved' => 'success',
                        'rejected' => 'danger',
                        'disbursed' => 'success',
                        'cancelled' => 'gray',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => FundingApplication::getStatuses()[$state] ?? $state),
                
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime('M d, Y')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options(FundingApplication::getStatuses()),
            ])
            ->headerActions([])
            ->actions([
                Tables\Actions\Action::make('view')
                    ->icon('heroicon-o-eye')
                    ->url(fn ($record) => route('filament.admin.resources.funding-applications.view', $record)),
            ])
            ->bulkActions([]);
    }
}
