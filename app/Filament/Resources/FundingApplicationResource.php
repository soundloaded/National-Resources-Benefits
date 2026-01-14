<?php

namespace App\Filament\Resources;

use App\Filament\Resources\FundingApplicationResource\Pages;
use App\Models\FundingApplication;
use App\Models\FundingSource;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;

class FundingApplicationResource extends Resource
{
    protected static ?string $model = FundingApplication::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationGroup = 'Content Management';
    protected static ?string $navigationLabel = 'Funding Applications';
    protected static ?int $navigationSort = 7;

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('status', 'pending')->count() ?: null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Application Details')
                    ->schema([
                        Forms\Components\TextInput::make('application_number')
                            ->disabled()
                            ->dehydrated(false),
                        
                        Forms\Components\Select::make('user_id')
                            ->relationship('user', 'name')
                            ->searchable()
                            ->preload()
                            ->disabled()
                            ->dehydrated(false),
                        
                        Forms\Components\Select::make('funding_source_id')
                            ->relationship('fundingSource', 'title')
                            ->searchable()
                            ->preload()
                            ->disabled()
                            ->dehydrated(false),
                        
                        Forms\Components\TextInput::make('requested_amount')
                            ->prefix('$')
                            ->numeric()
                            ->disabled()
                            ->dehydrated(false),
                        
                        Forms\Components\Textarea::make('purpose')
                            ->rows(3)
                            ->disabled()
                            ->dehydrated(false)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Uploaded Documents')
                    ->schema([
                        Forms\Components\Placeholder::make('documents_display')
                            ->label('')
                            ->content(function ($record) {
                                if (!$record || empty($record->documents)) {
                                    return 'No documents uploaded';
                                }
                                $html = '<div class="space-y-2">';
                                foreach ($record->documents as $doc) {
                                    $name = $doc['name'] ?? 'Document';
                                    $path = $doc['path'] ?? '';
                                    $url = asset('storage/' . $path);
                                    $html .= "<div class='flex items-center gap-2'>";
                                    $html .= "<span class='text-sm'>{$name}</span>";
                                    $html .= "<a href='{$url}' target='_blank' class='text-primary-500 hover:underline text-sm'>View</a>";
                                    $html .= "</div>";
                                }
                                $html .= '</div>';
                                return new \Illuminate\Support\HtmlString($html);
                            })
                            ->columnSpanFull(),
                    ])
                    ->collapsible(),

                Forms\Components\Section::make('Review & Decision')
                    ->schema([
                        Forms\Components\Select::make('status')
                            ->options(FundingApplication::getStatuses())
                            ->required()
                            ->native(false),
                        
                        Forms\Components\TextInput::make('approved_amount')
                            ->prefix('$')
                            ->numeric()
                            ->visible(fn ($get) => in_array($get('status'), ['approved', 'disbursed'])),
                        
                        Forms\Components\Textarea::make('admin_notes')
                            ->label('Internal Notes')
                            ->rows(3)
                            ->helperText('These notes are only visible to admins')
                            ->columnSpanFull(),
                        
                        Forms\Components\Textarea::make('rejection_reason')
                            ->rows(3)
                            ->visible(fn ($get) => $get('status') === 'rejected')
                            ->required(fn ($get) => $get('status') === 'rejected')
                            ->helperText('This will be shown to the applicant')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('application_number')
                    ->label('App #')
                    ->searchable()
                    ->sortable()
                    ->copyable(),
                
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Applicant')
                    ->searchable()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('fundingSource.title')
                    ->label('Funding Source')
                    ->limit(30)
                    ->searchable()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('requested_amount')
                    ->money('USD')
                    ->sortable(),
                
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
                
                Tables\Columns\TextColumn::make('approved_amount')
                    ->money('USD')
                    ->sortable()
                    ->toggleable(),
                
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Applied')
                    ->dateTime('M d, Y')
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('reviewed_at')
                    ->label('Reviewed')
                    ->dateTime('M d, Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->options(FundingApplication::getStatuses()),
                
                SelectFilter::make('funding_source_id')
                    ->label('Funding Source')
                    ->relationship('fundingSource', 'title')
                    ->searchable()
                    ->preload(),
            ])
            ->actions([
                Tables\Actions\Action::make('review')
                    ->icon('heroicon-o-eye')
                    ->color('info')
                    ->modalHeading('Review Application')
                    ->modalWidth('4xl')
                    ->form([
                        Forms\Components\Select::make('status')
                            ->options([
                                'under_review' => 'Mark as Under Review',
                                'approved' => 'Approve',
                                'rejected' => 'Reject',
                            ])
                            ->required(),
                        Forms\Components\TextInput::make('approved_amount')
                            ->prefix('$')
                            ->numeric()
                            ->visible(fn ($get) => $get('status') === 'approved')
                            ->required(fn ($get) => $get('status') === 'approved'),
                        Forms\Components\Textarea::make('rejection_reason')
                            ->visible(fn ($get) => $get('status') === 'rejected')
                            ->required(fn ($get) => $get('status') === 'rejected'),
                        Forms\Components\Textarea::make('admin_notes')
                            ->label('Internal Notes'),
                    ])
                    ->action(function (FundingApplication $record, array $data) {
                        $record->update([
                            'status' => $data['status'],
                            'approved_amount' => $data['approved_amount'] ?? null,
                            'rejection_reason' => $data['rejection_reason'] ?? null,
                            'admin_notes' => $data['admin_notes'] ?? $record->admin_notes,
                            'reviewed_by' => auth()->id(),
                            'reviewed_at' => now(),
                        ]);

                        Notification::make()
                            ->success()
                            ->title('Application Updated')
                            ->body("Application {$record->application_number} has been updated.")
                            ->send();
                    })
                    ->visible(fn (FundingApplication $record) => in_array($record->status, ['pending', 'under_review'])),
                
                Tables\Actions\Action::make('disburse')
                    ->icon('heroicon-o-banknotes')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Confirm Disbursement')
                    ->modalDescription('This will disburse the approved funds to the applicant\'s account and create a transaction record.')
                    ->action(function (FundingApplication $record) {
                        // Get user's primary account
                        $account = $record->user->accounts()->first();
                        
                        if ($account && $record->approved_amount > 0) {
                            // Create transaction record
                            \App\Models\Transaction::create([
                                'account_id' => $account->id,
                                'transaction_type' => 'funding_disbursement',
                                'amount' => $record->approved_amount,
                                'currency' => 'USD',
                                'status' => 'completed',
                                'description' => "Funding disbursement for application {$record->application_number} - {$record->fundingSource->title}",
                                'reference_number' => 'FUND-' . strtoupper(uniqid()),
                                'metadata' => [
                                    'funding_application_id' => $record->id,
                                    'funding_source_id' => $record->funding_source_id,
                                    'application_number' => $record->application_number,
                                ],
                                'completed_at' => now(),
                            ]);
                            
                            // Credit the account balance
                            $account->increment('balance', $record->approved_amount);
                        }
                        
                        $record->update([
                            'status' => 'disbursed',
                            'disbursed_at' => now(),
                        ]);

                        Notification::make()
                            ->success()
                            ->title('Funds Disbursed')
                            ->body("\${$record->approved_amount} has been disbursed to {$record->user->name}.")
                            ->send();
                    })
                    ->visible(fn (FundingApplication $record) => $record->status === 'approved'),
                
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Application Information')
                    ->schema([
                        Infolists\Components\TextEntry::make('application_number')
                            ->copyable(),
                        Infolists\Components\TextEntry::make('user.name')
                            ->label('Applicant'),
                        Infolists\Components\TextEntry::make('user.email')
                            ->label('Email'),
                        Infolists\Components\TextEntry::make('fundingSource.title')
                            ->label('Funding Source'),
                        Infolists\Components\TextEntry::make('requested_amount')
                            ->money('USD'),
                        Infolists\Components\TextEntry::make('status')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'pending' => 'warning',
                                'under_review' => 'info',
                                'approved' => 'success',
                                'rejected' => 'danger',
                                'disbursed' => 'success',
                                'cancelled' => 'gray',
                                default => 'gray',
                            }),
                        Infolists\Components\TextEntry::make('created_at')
                            ->label('Applied On')
                            ->dateTime(),
                    ])
                    ->columns(3),
                
                Infolists\Components\Section::make('Purpose')
                    ->schema([
                        Infolists\Components\TextEntry::make('purpose')
                            ->prose()
                            ->columnSpanFull(),
                    ]),
                
                Infolists\Components\Section::make('Review Details')
                    ->schema([
                        Infolists\Components\TextEntry::make('approved_amount')
                            ->money('USD'),
                        Infolists\Components\TextEntry::make('reviewer.name')
                            ->label('Reviewed By'),
                        Infolists\Components\TextEntry::make('reviewed_at')
                            ->dateTime(),
                        Infolists\Components\TextEntry::make('rejection_reason')
                            ->visible(fn ($record) => $record->status === 'rejected'),
                        Infolists\Components\TextEntry::make('admin_notes')
                            ->label('Internal Notes'),
                    ])
                    ->columns(2)
                    ->visible(fn ($record) => $record->reviewed_at !== null),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListFundingApplications::route('/'),
            'view' => Pages\ViewFundingApplication::route('/{record}'),
            'edit' => Pages\EditFundingApplication::route('/{record}/edit'),
        ];
    }
}
