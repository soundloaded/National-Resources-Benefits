<?php

namespace App\Filament\Clusters\LoanManagement\Resources;

use App\Filament\Clusters\LoanManagement;
use App\Filament\Clusters\LoanManagement\Resources\GrantResource\Pages;
use App\Models\Grant;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;

class GrantResource extends Resource
{
    protected static ?string $model = Grant::class;

    protected static ?string $navigationIcon = 'heroicon-o-gift';

    protected static ?string $cluster = LoanManagement::class;

    protected static ?string $navigationLabel = 'Grants & Funding';
    
    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Grant Information')
                    ->schema([
                        Forms\Components\TextInput::make('title')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),
                        
                        Forms\Components\MarkdownEditor::make('description')
                            ->required()
                            ->columnSpanFull(),
                        
                        Forms\Components\Grid::make(2)->schema([
                            Forms\Components\Select::make('grant_category_id')
                                ->label('Category')
                                ->relationship('category', 'name')
                                ->searchable()
                                ->preload()
                                ->required(),
                            
                            Forms\Components\Select::make('funding_source')
                                ->options([
                                    'Government' => 'Government',
                                    'Private' => 'Private Foundation',
                                    'Non-profit' => 'Non-profit Organization',
                                    'Corporate' => 'Corporate Sponsor',
                                ])
                                ->searchable(),
                        ]),
                    ]),

                Forms\Components\Section::make('Funding Details')
                    ->schema([
                        Forms\Components\Grid::make(2)->schema([
                            Forms\Components\TextInput::make('min_amount')
                                ->label('Minimum Amount')
                                ->numeric()
                                ->prefix('$'),
                            
                            Forms\Components\TextInput::make('max_amount')
                                ->label('Maximum Amount')
                                ->numeric()
                                ->prefix('$'),
                        ]),
                        
                        Forms\Components\TextInput::make('application_deadline')
                            ->label('Application Deadline')
                            ->placeholder('e.g., Rolling, December 31, 2026'),
                        
                        Forms\Components\TextInput::make('url')
                            ->label('Application URL')
                            ->url()
                            ->placeholder('https://example.com/apply')
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('Eligibility & Requirements')
                    ->schema([
                        Forms\Components\Textarea::make('eligibility_criteria')
                            ->label('Eligibility Criteria')
                            ->rows(3)
                            ->placeholder('Who can apply for this grant?')
                            ->columnSpanFull(),
                        
                        Forms\Components\Textarea::make('requirements')
                            ->label('Required Documents/Information')
                            ->rows(3)
                            ->placeholder('List required documents, forms, etc.')
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('Status')
                    ->schema([
                        Forms\Components\Select::make('status')
                            ->options([
                                'active' => 'Active',
                                'inactive' => 'Inactive',
                                'expired' => 'Expired',
                            ])
                            ->default('active')
                            ->required(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->wrap(),
                
                Tables\Columns\TextColumn::make('category.name')
                    ->label('Category')
                    ->badge()
                    ->color(fn ($state) => 'primary')
                    ->searchable()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('funding_source')
                    ->label('Source')
                    ->badge()
                    ->searchable(),
                
                Tables\Columns\TextColumn::make('min_amount')
                    ->label('Min')
                    ->money('USD')
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('max_amount')
                    ->label('Max')
                    ->money('USD')
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('application_deadline')
                    ->label('Deadline')
                    ->limit(20)
                    ->searchable(),
                
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'active' => 'success',
                        'inactive' => 'warning',
                        'expired' => 'danger',
                        default => 'gray',
                    }),
                
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('grant_category_id')
                    ->label('Category')
                    ->relationship('category', 'name'),
                
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'active' => 'Active',
                        'inactive' => 'Inactive',
                        'expired' => 'Expired',
                    ]),
            ])
            ->actions([
                Tables\Actions\Action::make('disburse')
                    ->icon('heroicon-o-banknotes')
                    ->color('success')
                    ->label('Award Grant')
                    ->modalHeading('Award Grant to User')
                    ->modalDescription('Disburse grant funds directly to a user\'s account.')
                    ->form([
                        Forms\Components\Select::make('user_id')
                            ->label('Recipient')
                            ->options(User::query()->pluck('name', 'id'))
                            ->searchable()
                            ->required(),
                        Forms\Components\TextInput::make('amount')
                            ->label('Amount')
                            ->numeric()
                            ->prefix('$')
                            ->required()
                            ->minValue(1),
                        Forms\Components\Textarea::make('notes')
                            ->label('Notes')
                            ->placeholder('Reason for awarding this grant...'),
                    ])
                    ->action(function (Grant $record, array $data) {
                        $user = User::find($data['user_id']);
                        $account = $user->accounts()->first();
                        
                        if ($account) {
                            \App\Models\Transaction::create([
                                'account_id' => $account->id,
                                'transaction_type' => 'grant',
                                'amount' => $data['amount'],
                                'currency' => 'USD',
                                'status' => 'completed',
                                'description' => "Grant disbursement: {$record->title}" . ($data['notes'] ? " - {$data['notes']}" : ''),
                                'reference_number' => 'GRANT-' . strtoupper(uniqid()),
                                'metadata' => [
                                    'grant_id' => $record->id,
                                    'grant_title' => $record->title,
                                    'notes' => $data['notes'] ?? null,
                                ],
                                'completed_at' => now(),
                            ]);
                            
                            $account->increment('balance', $data['amount']);
                            
                            Notification::make()
                                ->success()
                                ->title('Grant Disbursed')
                                ->body("\${$data['amount']} has been awarded to {$user->name}.")
                                ->send();
                        } else {
                            Notification::make()
                                ->danger()
                                ->title('Error')
                                ->body('User does not have an account.')
                                ->send();
                        }
                    }),
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
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
            'index' => Pages\ListGrants::route('/'),
            'create' => Pages\CreateGrant::route('/create'),
            'view' => Pages\ViewGrant::route('/{record}'),
            'edit' => Pages\EditGrant::route('/{record}/edit'),
        ];
    }
}
