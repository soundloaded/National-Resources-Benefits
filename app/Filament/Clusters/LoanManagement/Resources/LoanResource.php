<?php

namespace App\Filament\Clusters\LoanManagement\Resources;

use App\Filament\Clusters\LoanManagement;
use App\Filament\Clusters\LoanManagement\Resources\LoanResource\Pages;
use App\Filament\Clusters\LoanManagement\Resources\LoanResource\RelationManagers;
use App\Models\Loan;
use App\Models\LoanPlan;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class LoanResource extends Resource
{
    protected static ?string $model = Loan::class;

    protected static ?string $navigationIcon = 'heroicon-o-clock';

    protected static ?string $navigationLabel = 'Loan History';

    protected static ?string $modelLabel = 'Loan Application';

    protected static ?string $pluralModelLabel = 'Loan Applications';

    protected static ?string $cluster = LoanManagement::class;
    
    protected static ?int $navigationSort = 2;

    protected static ?string $slug = 'history';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('user_id')
                    ->relationship('user', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),

                Forms\Components\Select::make('loan_plan_id')
                    ->label('Loan Plan')
                    ->relationship('loanPlan', 'name')
                    ->searchable()
                    ->preload()
                    ->live()
                    ->afterStateUpdated(function (Get $get, Set $set, ?string $state) {
                        if ($state) {
                            $plan = LoanPlan::find($state);
                            if ($plan) {
                                $set('interest_rate', $plan->interest_rate);
                            }
                        }
                    }),
                
                Forms\Components\Section::make('Loan Details')->schema([
                    Forms\Components\TextInput::make('amount')
                        ->required()
                        ->numeric()
                        ->prefix('$')
                        ->live(onBlur: true)
                        ->afterStateUpdated(function (Get $get, Set $set) {
                            $amount = (float) $get('amount');
                            $rate = (float) $get('interest_rate');
                            $total = $amount + ($amount * ($rate / 100));
                            $set('total_payable', round($total, 2));
                        }),

                    Forms\Components\TextInput::make('interest_rate')
                        ->required()
                        ->numeric()
                        ->suffix('%')
                        ->default(fn () => \App\Models\Setting::get('loan_interest_rate', 5))
                        ->live(onBlur: true)
                        ->afterStateUpdated(function (Get $get, Set $set) {
                            $amount = (float) $get('amount');
                            $rate = (float) $get('interest_rate');
                            $total = $amount + ($amount * ($rate / 100));
                            $set('total_payable', round($total, 2));
                        }),

                    Forms\Components\TextInput::make('total_payable')
                        ->required()
                        ->numeric()
                        ->prefix('$')
                        ->readOnly(),

                    Forms\Components\TextInput::make('duration_days')
                        ->label('Duration (Days)')
                        ->required()
                        ->numeric()
                        ->default(30),
                ])->columns(2),

                Forms\Components\Section::make('Status & Notes')->schema([
                    Forms\Components\Select::make('status')
                        ->options([
                            'pending' => 'Pending',
                            'approved' => 'Approved',
                            'rejected' => 'Rejected',
                            'active' => 'Active',
                            'completed' => 'Completed',
                            'overdue' => 'Overdue',
                        ])
                        ->required()
                        ->default('pending'),

                    Forms\Components\Textarea::make('reason')
                        ->label('Customer Reason')
                        ->columnSpanFull(),

                    Forms\Components\Textarea::make('admin_note')
                        ->label('Admin Note')
                        ->columnSpanFull(),
                ])->columns(1),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('loan_id')
                    ->label('Loan ID')
                    ->searchable()
                    ->copyable(),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Customer')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('amount')
                    ->money('USD')
                    ->sortable(),
                Tables\Columns\TextColumn::make('total_payable')
                    ->label('Payable')
                    ->money('USD')
                    ->sortable(),
                Tables\Columns\TextColumn::make('interest_rate')
                    ->label('Rate')
                    ->suffix('%'),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'approved', 'active', 'completed' => 'success',
                        'rejected', 'overdue' => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'approved' => 'Approved',
                        'rejected' => 'Rejected',
                        'active' => 'Active',
                        'completed' => 'Completed',
                        'overdue' => 'Overdue',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->modalWidth('lg'),
                Tables\Actions\Action::make('approve')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Approve & Disburse Loan')
                    ->modalDescription('This will approve the loan and disburse the funds to the user\'s account.')
                    ->action(function (Loan $record) {
                        // Get user's primary account
                        $account = $record->user->accounts()->first();
                        
                        if ($account) {
                            // Create transaction record for loan disbursement
                            \App\Models\Transaction::create([
                                'account_id' => $account->id,
                                'transaction_type' => 'loan',
                                'amount' => $record->amount,
                                'currency' => 'USD',
                                'status' => 'completed',
                                'description' => "Loan disbursement - {$record->loan_id}",
                                'reference_number' => 'LOAN-' . strtoupper(uniqid()),
                                'metadata' => [
                                    'loan_id' => $record->id,
                                    'loan_reference' => $record->loan_id,
                                    'interest_rate' => $record->interest_rate,
                                    'duration_days' => $record->duration_days,
                                ],
                                'completed_at' => now(),
                            ]);
                            
                            // Credit the account balance
                            $account->increment('balance', $record->amount);
                        }
                        
                        $record->update([
                            'status' => 'approved',
                            'approved_at' => now(),
                        ]);
                    })
                    ->visible(fn (Loan $record) => $record->status === 'pending'),
                Tables\Actions\Action::make('reject')
                    ->icon('heroicon-o-x-mark')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(fn (Loan $record) => $record->update(['status' => 'rejected']))
                    ->visible(fn (Loan $record) => $record->status === 'pending'),
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
            'index' => Pages\ListLoans::route('/'),
        ];
    }
}
