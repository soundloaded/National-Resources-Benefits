<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Helpers\Avatar;
use App\Helpers\Currency;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists\Components;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Hash;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';
    protected static ?string $navigationGroup = 'User Management';
    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'name';

    public static function getGlobalSearchResultDetails(Model $record): array
    {
        return [
            'Email' => $record->email,
        ];
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Components\Grid::make([
                    'default' => 1,
                    'lg' => 3,
                ])->schema([
                    // LEFT COLUMN (1/3 width)
                    Components\Group::make([
                        // Profile Card
                        Components\Section::make()
                            ->schema([
                                Components\ImageEntry::make('avatar_url')
                                    ->hiddenLabel()
                                    ->circular()
                                    ->height(120)
                                    ->alignCenter()
                                    ->defaultImageUrl(fn ($record) => Avatar::gravatar($record->email, 200, 'mp')),

                                Components\TextEntry::make('name')
                                    ->hiddenLabel()
                                    ->weight('bold')
                                    ->size('lg')
                                    ->alignCenter(),

                                Components\TextEntry::make('email') // Using email as country placeholder if country missing
                                    ->hiddenLabel()
                                    ->alignCenter()
                                    ->color('gray'),
                                
                                Components\TextEntry::make('last_login_at')
                                    ->label('Last Login')
                                    ->alignCenter()
                                    ->icon('heroicon-o-clock')
                                    ->dateTime('Y-m-d H:i')
                                    ->color('gray')
                                    ->placeholder('Never'),

                                // Action Buttons
                                Components\Actions::make([
                                    Components\Actions\Action::make('notify')
                                        ->icon('heroicon-o-bell')
                                        ->color('warning')
                                        ->tooltip('Notify User')
                                        ->modalWidth('md')
                                        ->form([
                                            Forms\Components\Select::make('notify_type')
                                                ->label('Notification Channel')
                                                ->options([
                                                    'database' => 'Push Notification (In-App)',
                                                    'mail' => 'Email',
                                                    'both' => 'Both (Email & Push)',
                                                ])
                                                ->default('database')
                                                ->required(),
                                            Forms\Components\TextInput::make('title')
                                                ->required(),
                                            Forms\Components\MarkdownEditor::make('message')
                                                ->required(),
                                        ])
                                        ->action(function (array $data, User $record) {
                                            $channels = match ($data['notify_type']) {
                                                'mail' => ['mail'],
                                                'both' => ['mail', 'database'],
                                                default => ['database'],
                                            };
                                            
                                            $record->notify(new \App\Notifications\GeneralAnnouncement(
                                                $data['title'], 
                                                $data['message'], 
                                                $channels
                                            ));

                                            \Filament\Notifications\Notification::make()
                                                ->title('Notification Sent')
                                                ->body('User has been notified successfully.')
                                                ->success()
                                                ->send();
                                        }),
                                        
                                    Components\Actions\Action::make('manage_funds')
                                        ->icon('heroicon-o-wallet')
                                        ->color('success')
                                        ->tooltip('Manage Funds')
                                        ->modalWidth('md')
                                        ->form([
                                            Forms\Components\Select::make('account_id')
                                                ->label('Select Wallet')
                                                ->options(function (User $record) {
                                                    return $record->accounts()->with('walletType')->get()->mapWithKeys(function ($account) {
                                                        $name = $account->walletType->name ?? $account->account_type; // Fallback
                                                        return [$account->id => "{$name} ({$account->currency}) - " . number_format($account->balance, 2)];
                                                    });
                                                })
                                                ->required(),
                                            Forms\Components\Radio::make('type')
                                                ->options([
                                                    'credit' => 'Credit',
                                                    'debit' => 'Debit',
                                                ])
                                                ->required(),
                                            Forms\Components\TextInput::make('amount')
                                                ->numeric()
                                                ->prefix(Currency::getSymbol())
                                                ->required(),
                                            Forms\Components\TextInput::make('description')
                                                ->maxLength(100),
                                            Forms\Components\Textarea::make('note')
                                                ->rows(2),
                                        ])
                                        ->action(function (array $data, User $record) {
                                            $account = $record->accounts()->find($data['account_id']);
                                            if ($account) {
                                                if ($data['type'] === 'credit') {
                                                    $account->increment('balance', $data['amount']);
                                                } else {
                                                    $account->decrement('balance', $data['amount']);
                                                }
                                                
                                                // Create Transaction
                                                \App\Models\Transaction::create([
                                                    'account_id' => $account->id,
                                                    'transaction_type' => $data['type'] === 'credit' ? 'deposit' : 'withdrawal',
                                                    'amount' => $data['amount'],
                                                    'description' => $data['description'] ?? ucfirst($data['type']) . ' Adjustment',
                                                    'status' => 'completed',
                                                    'reference_number' => \Illuminate\Support\Str::uuid(),
                                                ]);
                                                
                                                // Notify User (Push + Email)
                                                $actionType = ucfirst($data['type']);
                                                $record->notify(new \App\Notifications\GeneralAnnouncement(
                                                    "Funds {$actionType}ed",
                                                    "Your wallet ({$account->currency}) has been {$data['type']}ed with {$account->currency} " . number_format($data['amount'], 2) . ". New Balance: {$account->currency} " . number_format($account->balance, 2),
                                                    ['database', 'mail']
                                                ));

                                                // Notify Admin (Toast)
                                                \Filament\Notifications\Notification::make()
                                                    ->title('Balance Updated')
                                                    ->body("User notified via Email & Push. Transaction recorded.")
                                                    ->success()
                                                    ->send();
                                            }
                                        }),

                                    Components\Actions\Action::make('login_as')
                                        ->icon('heroicon-o-user') 
                                        ->color('gray')
                                        ->tooltip('Login as User')
                                        ->url(fn (User $record) => route('admin.impersonate', $record))
                                        ->openUrlInNewTab(),

                                    Components\Actions\Action::make('transfer_codes')
                                        ->icon('heroicon-o-key')
                                        ->color('primary')
                                        ->tooltip('Manage Transfer Codes')
                                        ->modalWidth('md')
                                        ->fillForm(fn (User $record) => [
                                            'imf_code' => $record->imf_code,
                                            'tax_code' => $record->tax_code,
                                            'cot_code' => $record->cot_code,
                                        ])
                                        ->form([
                                            Forms\Components\TextInput::make('imf_code')->label('IMF Code'),
                                            Forms\Components\TextInput::make('tax_code')->label('TAX Code'),
                                            Forms\Components\TextInput::make('cot_code')->label('COT Code'),
                                        ])
                                        ->action(function (array $data, User $record) {
                                            $record->update($data);
                                            \Filament\Notifications\Notification::make()
                                                ->title('Codes Updated')
                                                ->success()
                                                ->send();
                                        }),

                                    Components\Actions\Action::make('withdrawal_control')
                                        ->icon('heroicon-o-no-symbol')
                                        ->color('info')
                                        ->tooltip('Withdrawal Control')
                                        ->modalWidth('md')
                                        ->fillForm(fn (User $record) => [
                                            'withdrawal_status' => $record->withdrawal_status,
                                        ])
                                        ->form([
                                            Forms\Components\Select::make('withdrawal_status')
                                                ->options([
                                                    'allowed' => 'Allowed',
                                                    'pending' => 'Pending Review',
                                                    'hold' => 'On Hold',
                                                    'suspended' => 'Suspended',
                                                    'restricted' => 'Restricted',
                                                ])
                                                ->required(),
                                            Forms\Components\Textarea::make('admin_note')
                                                ->placeholder('Internal notes...'),
                                            Forms\Components\Textarea::make('user_message')
                                                ->placeholder('Message shown to user...'),
                                        ])
                                        ->action(function (array $data, User $record) {
                                            $record->update(['withdrawal_status' => $data['withdrawal_status']]);
                                            \Filament\Notifications\Notification::make()
                                                ->title('Status Updated')
                                                ->success()
                                                ->send();
                                        }),
                                ])->alignCenter()->fullWidth(false),
                            ]),

                        // Wallets List
                        Components\Section::make('Wallets')
                            ->headerActions([
                                Components\Actions\Action::make('add_wallet')
                                    ->label('Assign Wallet')
                                    ->icon('heroicon-o-plus')
                                    ->color('primary')
                                    ->modalWidth('md')
                                    ->form([
                                        Forms\Components\Select::make('wallet_type_id')
                                            ->label('Wallet Type')
                                            ->options(\App\Models\WalletType::where('is_active', true)->pluck('name', 'id'))
                                            ->required()
                                            ->searchable(),
                                        Forms\Components\TextInput::make('initial_balance')
                                            ->numeric()
                                            ->default(0),
                                    ])
                                    ->action(function (array $data, User $record) {
                                        $walletType = \App\Models\WalletType::find($data['wallet_type_id']);
                                        $record->accounts()->create([
                                            'wallet_type_id' => $walletType->id,
                                            'currency' => $walletType->currency_code,
                                            'balance' => $data['initial_balance'],
                                            'account_number' => 'ACC-' . strtoupper(uniqid()),
                                            'status' => 'active',
                                            'account_type' => 'checking', // Fallback for legacy schema
                                        ]);
                                        
                                        \Filament\Notifications\Notification::make()
                                            ->title('Wallet assigned successfully')
                                            ->success()
                                            ->send();
                                    }),
                            ])
                            ->schema([
                                Components\RepeatableEntry::make('accounts')
                                    ->hiddenLabel()
                                    ->schema([
                                        Components\Grid::make(2)->schema([
                                            Components\Group::make([
                                                Components\TextEntry::make('walletType.name')
                                                    ->label('Type')
                                                    ->weight('bold')
                                                    ->default('Standard Wallet'),
                                                Components\TextEntry::make('account_number')
                                                    ->label('Account #')
                                                    ->color('gray')
                                                    ->copyable(),
                                            ]),
                                            Components\TextEntry::make('balance')
                                                ->money(fn ($record) => $record->currency)
                                                ->weight('bold')
                                                ->alignRight()
                                                ->size('lg'),
                                        ]),
                                    ])
                                    ->contained(false) 
                            ])->collapsible(),

                        // User Controls (Livewire)
                        Components\Section::make('User Controls')
                            ->schema([
                                Components\Livewire::make(\App\Livewire\UserControls::class)
                                    ->key('user-controls'),
                            ]),

                    ])->columnSpan(['lg' => 1]),

                    // RIGHT COLUMN (2/3 width)
                    Components\Group::make([
                        Components\Section::make() // Wrap in section for card effect
                            ->schema([
                                Components\Tabs::make('User Details')
                                    ->tabs([
                                        // TAB 1: STATISTICS
                                        Components\Tabs\Tab::make('Statistics')
                                            ->icon('heroicon-m-chart-bar')
                                            ->schema([
                                                // Stats Grid
                                                Components\Grid::make(3)->schema([
                                                    Components\TextEntry::make('stat_trx_total')
                                                        ->label('Total Transactions')
                                                        ->state(fn (User $record) => $record->transactions()->count()) 
                                                        ->icon('heroicon-o-arrows-right-left')
                                                        ->color('primary'),
                                                    Components\TextEntry::make('stat_trx_completed')
                                                        ->label('Completed Transactions')
                                                        ->state(fn (User $record) => $record->transactions()->where('transactions.status', 'completed')->count())
                                                        ->icon('heroicon-o-check-circle')
                                                        ->color('success'),
                                                    Components\TextEntry::make('stat_trx_pending')
                                                        ->label('Pending Transactions')
                                                        ->state(fn (User $record) => $record->transactions()->where('transactions.status', 'pending')->count())
                                                        ->icon('heroicon-o-clock')
                                                        ->color('warning'),
                                                    Components\TextEntry::make('stat_trx_failed')
                                                        ->label('Failed Transactions')
                                                        ->state(fn (User $record) => $record->transactions()->whereIn('transactions.status', ['failed', 'cancelled'])->count())
                                                        ->icon('heroicon-o-x-circle')
                                                        ->color('danger'),
                                                    Components\TextEntry::make('stat_deposit')
                                                        ->label('Total Deposits')
                                                        ->money('USD')
                                                        ->state(fn (User $record) => $record->transactions()->where('transactions.transaction_type', 'deposit')->where('transactions.status', 'completed')->sum('amount'))
                                                        ->icon('heroicon-o-arrow-down-tray')
                                                        ->color('success'),
                                                    Components\TextEntry::make('stat_withdraw')
                                                        ->label('Total Withdrawals')
                                                        ->money('USD')
                                                        ->state(fn (User $record) => $record->transactions()->where('transactions.transaction_type', 'withdrawal')->where('transactions.status', 'completed')->sum('amount'))
                                                        ->icon('heroicon-o-arrow-up-tray')
                                                        ->color('warning'),
                                                    
                                                    // Full width stats
                                                    Components\TextEntry::make('stat_total_balance')
                                                        ->label('Total Balance (All Wallets)')
                                                        ->money('USD')
                                                        ->state(fn (User $record) => $record->accounts()->sum('balance'))
                                                        ->icon('heroicon-o-banknotes')
                                                        ->color('primary')
                                                        ->size('lg')
                                                        ->columnSpan(3),
                                                ]),
                                            ]),

                                        // TAB 2: PROFILE INFO (Existing)
                                        Components\Tabs\Tab::make('Profile Information')
                                            ->icon('heroicon-m-user')
                                            ->schema([
                                                Components\Section::make('Personal Details')
                                                    ->headerActions([
                                                        Components\Actions\Action::make('edit_profile')
                                                            ->label('Edit Details')
                                                            ->icon('heroicon-m-pencil-square')
                                                            ->modalWidth('lg')
                                                            ->fillForm(fn (User $record) => [
                                                                'name' => $record->name,
                                                                'email' => $record->email,
                                                                'phone' => $record->phone,
                                                                'age_range' => $record->age_range,
                                                                'gender' => $record->gender,
                                                                'ethnicity' => $record->ethnicity,
                                                                'employment_status' => $record->employment_status,
                                                                'citizenship_status' => $record->citizenship_status,
                                                                'zip_code' => $record->zip_code,
                                                                'city' => $record->city,
                                                                'state' => $record->state,
                                                            ])
                                                            ->form([
                                                                Forms\Components\Grid::make(2)->schema([
                                                                    Forms\Components\TextInput::make('name')
                                                                        ->required()
                                                                        ->maxLength(255),
                                                                    Forms\Components\TextInput::make('email')
                                                                        ->email()
                                                                        ->required()
                                                                        ->maxLength(255),
                                                                    Forms\Components\TextInput::make('phone')
                                                                        ->tel()
                                                                        ->maxLength(20),
                                                                    Forms\Components\Select::make('age_range')
                                                                        ->options([
                                                                            '18_25' => '18-25',
                                                                            '26_34' => '26-34',
                                                                            '35_49' => '35-49',
                                                                            '50_65' => '50-65',
                                                                            '66_80' => '66-80',
                                                                            '80_plus' => '80+',
                                                                        ]),
                                                                    Forms\Components\Select::make('gender')
                                                                        ->options([
                                                                            'male' => 'Male',
                                                                            'female' => 'Female',
                                                                        ]),
                                                                    Forms\Components\Select::make('ethnicity')
                                                                        ->options([
                                                                            'white_caucasian' => 'White/Caucasian',
                                                                            'african_american' => 'African American',
                                                                            'black' => 'Black',
                                                                            'hispanic' => 'Hispanic',
                                                                            'latino' => 'Latino',
                                                                            'asian' => 'Asian',
                                                                            'native_american' => 'Native American',
                                                                            'indigenous' => 'Indigenous',
                                                                            'arab' => 'Arab',
                                                                            'middle_eastern' => 'Middle Eastern',
                                                                            'pacific_islander' => 'Pacific Islander',
                                                                            'multi_racial' => 'Multi-Racial',
                                                                            'other' => 'Other',
                                                                        ]),
                                                                    Forms\Components\Select::make('employment_status')
                                                                        ->options([
                                                                            'employed_full_time' => 'Employed Full-Time',
                                                                            'employed_part_time' => 'Employed Part-Time',
                                                                            'self_employed' => 'Self-Employed',
                                                                            'unemployed' => 'Unemployed',
                                                                            'retired' => 'Retired',
                                                                            'student' => 'Student',
                                                                            'disabled' => 'Disabled',
                                                                        ]),
                                                                    Forms\Components\Select::make('citizenship_status')
                                                                        ->options([
                                                                            'us_citizen' => 'U.S. Citizen',
                                                                            'resident_alien' => 'Resident Alien',
                                                                            'green_card' => 'Green Card Holder',
                                                                            'permanent_resident' => 'Permanent Resident',
                                                                            'not_sure' => 'Not Sure',
                                                                        ]),
                                                                    Forms\Components\TextInput::make('zip_code')
                                                                        ->maxLength(10),
                                                                    Forms\Components\TextInput::make('city')
                                                                        ->maxLength(50),
                                                                    Forms\Components\TextInput::make('state')
                                                                        ->maxLength(50),
                                                                ]),
                                                            ])
                                                            ->action(function (User $record, array $data) {
                                                                $record->update($data);
                                                                \Filament\Notifications\Notification::make()
                                                                    ->title('Profile Information Updated')
                                                                    ->success()
                                                                    ->send();
                                                            }),
                                                    ])
                                                    ->schema([
                                                        Components\Grid::make(2)->schema([
                                                            Components\TextEntry::make('name'),
                                                            Components\TextEntry::make('email')
                                                                ->icon('heroicon-m-envelope'),
                                                            Components\TextEntry::make('phone')
                                                                ->icon('heroicon-m-phone'),
                                                            Components\TextEntry::make('age_range')
                                                                ->label('Age Range'),
                                                            Components\TextEntry::make('gender'),
                                                            Components\TextEntry::make('ethnicity'),
                                                            Components\TextEntry::make('employment_status')
                                                                ->label('Employment Status')
                                                                ->badge()
                                                                ->color('info'),
                                                            Components\TextEntry::make('citizenship_status')
                                                                ->badge()
                                                                ->color(fn (?string $state): string => match ($state) {
                                                                    'us_citizen' => 'success',
                                                                    'permanent_resident', 'green_card' => 'info',
                                                                    default => 'gray',
                                                                }),
                                                            Components\TextEntry::make('zip_code'),
                                                            Components\TextEntry::make('city'),
                                                            Components\TextEntry::make('state'),
                                                        ]),
                                                    ]),
                                            ]),

                                        // TAB 3: Funding Intent
                                        Components\Tabs\Tab::make('Funding Application')
                                            ->icon('heroicon-m-currency-dollar')
                                            ->schema([
                                                Components\Section::make('Application Details')
                                                    ->headerActions([
                                                        Components\Actions\Action::make('edit_funding')
                                                            ->label('Update Funding Request')
                                                            ->icon('heroicon-m-pencil-square')
                                                            ->modalWidth('md')
                                                            ->fillForm(fn (User $record) => [
                                                                'funding_amount' => $record->funding_amount,
                                                                'funding_category' => $record->funding_category,
                                                            ])
                                                            ->form([
                                                                Forms\Components\TextInput::make('funding_amount')
                                                                    ->label('Requested Amount')
                                                                    ->numeric()
                                                                    ->prefix(Currency::getSymbol())
                                                                    ->required(),
                                                                Forms\Components\Select::make('funding_category')
                                                                    ->options(fn () => \App\Models\FundingCategory::pluck('name', 'name')->toArray())
                                                                    ->searchable()
                                                                    ->required(),
                                                            ])
                                                            ->action(function (User $record, array $data) {
                                                                $record->update($data);
                                                                \Filament\Notifications\Notification::make()
                                                                    ->title('Funding Request Updated')
                                                                    ->success()
                                                                    ->send();
                                                            }),
                                                    ])
                                                    ->schema([
                                                        Components\Grid::make(2)->schema([
                                                            Components\TextEntry::make('funding_amount')
                                                                ->label('Requested Amount')
                                                                ->badge()
                                                                ->color('primary')
                                                                ->size('lg')
                                                                ->money('USD'),
                                                            Components\TextEntry::make('funding_category')
                                                                ->label('Funding Type')
                                                                ->icon('heroicon-o-tag')
                                                                ->badge()
                                                                ->color('info'),
                                                        ]),
                                                    ]),
                                            ]),

                                        // TAB 4: Transactions
                                        Components\Tabs\Tab::make('Transactions')
                                            ->icon('heroicon-m-arrows-right-left')
                                            ->badge(fn (User $record) => $record->transactions()->count())
                                            ->schema([
                                                Components\RepeatableEntry::make('transactions')
                                                    ->hiddenLabel()
                                                    ->columns(4)
                                                    ->schema([
                                                        Components\TextEntry::make('created_at')
                                                            ->label('Date')
                                                            ->dateTime('M j, Y H:i')
                                                            ->size('sm')
                                                            ->color('gray'),
                                                        Components\TextEntry::make('transaction_type')
                                                            ->label('Type')
                                                            ->badge()
                                                            ->color(fn (string $state): string => match ($state) {
                                                                'deposit' => 'success',
                                                                'withdrawal' => 'warning',
                                                                default => 'primary',
                                                            }),
                                                        Components\TextEntry::make('amount')
                                                            ->label('Amount')
                                                            ->money('USD') // Ideally dynamic currency
                                                            ->weight('bold'),
                                                        Components\TextEntry::make('status')
                                                            ->badge()
                                                            ->color(fn (string $state): string => match ($state) {
                                                                'completed' => 'success',
                                                                'pending' => 'warning',
                                                                'failed', 'cancelled' => 'danger',
                                                                default => 'gray',
                                                            }),
                                                    ])
                                                    ->grid(1) // Full width list items
                                                    ->contained(false),
                                            ]),

                                        // TAB 5: Referrals
                                        Components\Tabs\Tab::make('Referrals')
                                            ->icon('heroicon-m-users')
                                            ->badge(fn (User $record) => $record->referrals()->count())
                                            ->schema([
                                                Components\Grid::make(2)->schema([
                                                    Components\TextEntry::make('referral_code')
                                                        ->label('User Referral Code')
                                                        ->copyable(),
                                                    Components\TextEntry::make('referrer.name')
                                                        ->label('Referred By')
                                                        ->placeholder('Direct Sign-up'),
                                                ]),
                                                Components\Section::make('Referral List')
                                                    ->schema([
                                                        Components\RepeatableEntry::make('referrals')
                                                            ->hiddenLabel()
                                                            ->columns(3)
                                                            ->schema([
                                                                Components\TextEntry::make('name')
                                                                    ->icon('heroicon-o-user'),
                                                                Components\TextEntry::make('email')
                                                                    ->color('gray'),
                                                                Components\TextEntry::make('created_at')
                                                                    ->label('Joined')
                                                                    ->date(),
                                                            ]),
                                                    ]),
                                            ]),

                                        // TAB 6: Activity Log
                                        Components\Tabs\Tab::make('Activity Log')
                                            ->icon('heroicon-m-clipboard-document-list')
                                            ->schema([
                                                Components\Section::make('Rank History')
                                                    ->schema([
                                                        Components\RepeatableEntry::make('rankHistory')
                                                            ->hiddenLabel()
                                                            ->schema([
                                                                Components\Grid::make(3)->schema([
                                                                    Components\TextEntry::make('old_rank_id')
                                                                        ->label('From Rank')
                                                                        ->formatStateUsing(fn ($state) => \App\Models\Rank::find($state)?->name ?? 'None'),
                                                                    Components\TextEntry::make('new_rank_id')
                                                                        ->label('To Rank')
                                                                        ->formatStateUsing(fn ($state) => \App\Models\Rank::find($state)?->name ?? 'None')
                                                                        ->weight('bold'),
                                                                    Components\TextEntry::make('created_at')
                                                                        ->dateTime(),
                                                                ]),
                                                            ]),
                                                    ]),
                                                    
                                                Components\Section::make('Recent Notifications')
                                                    ->collapsed()
                                                    ->schema([
                                                        Components\RepeatableEntry::make('recentNotifications')
                                                            ->hiddenLabel()
                                                            ->schema([
                                                                Components\TextEntry::make('data.title')
                                                                    ->label('Title')
                                                                    ->weight('bold'),
                                                                Components\TextEntry::make('data.message')
                                                                    ->label('Message')
                                                                    ->limit(50),
                                                                Components\TextEntry::make('created_at')
                                                                    ->dateTime()
                                                                    ->alignRight(),
                                                            ]),
                                                    ]),
                                            ]),
                                    ]),
                            ]),
                    ])->columnSpan(['lg' => 2]),

                ]) // End Main Grid
            ]);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('email')
                    ->email()
                    ->required()
                    ->maxLength(255),
                Forms\Components\Select::make('current_rank_id')
                    ->relationship('rank', 'name')
                    ->label('Rank Assignment')
                    ->searchable()
                    ->helperText('Manually override the user rank. This may be updated automatically based on transaction volume.')
                    ->preload(),
                Forms\Components\DateTimePicker::make('email_verified_at'),
                Forms\Components\TextInput::make('password')
                    ->password()
                    ->dehydrateStateUsing(fn ($state) => Hash::make($state))
                    ->dehydrated(fn ($state) => filled($state))
                    ->required(fn (string $context): bool => $context === 'create'),
                Forms\Components\Select::make('roles')
                    ->relationship('roles', 'name')
                    ->multiple()
                    ->preload()
                    ->searchable(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('email')
                    ->searchable(),
                Tables\Columns\TextColumn::make('roles.name')
                    ->badge(),
                Tables\Columns\IconColumn::make('email_verified_at')
                    ->label('Email Verified')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-badge')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger')
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('verify_email')
                    ->label('Verify Email')
                    ->icon('heroicon-o-check-badge')
                    ->color('success')
                    ->requiresConfirmation()
                    ->action(function (User $record) {
                        $record->email_verified_at = now();
                        $record->save();
                        Notification::make()
                            ->title('Email Verified')
                            ->success()
                            ->send();
                    })
                    ->visible(fn (User $record) => is_null($record->email_verified_at)),
                Tables\Actions\Action::make('unverify_email')
                    ->label('Unverify Email')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(function (User $record) {
                        $record->email_verified_at = null;
                        $record->save();
                        Notification::make()
                            ->title('Email Unverified')
                            ->success()
                            ->send();
                    })
                    ->visible(fn (User $record) => !is_null($record->email_verified_at)),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->recordUrl(fn (Model $record): string => Pages\ViewUser::getUrl([$record->id]));
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
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'view' => Pages\ViewUser::route('/{record}'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
