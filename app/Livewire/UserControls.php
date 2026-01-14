<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\User;
use App\Models\Account;
use App\Models\Transaction;
use Filament\Notifications\Notification;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Actions\Action;
use Filament\Forms\Form;
use Filament\Forms;
use Illuminate\Support\Facades\Auth;

class UserControls extends Component implements HasForms, HasActions
{
    use InteractsWithForms;
    use InteractsWithActions;

    public User $record;
    public ?array $data = [];

    public function mount(User $record): void
    {
        $this->record = $record;
        $this->form->fill([
            'is_active' => $record->is_active,
            'email_verified' => $record->email_verified_at !== null,
            'kyc_verified' => $record->kyc_verified_at !== null,
            'can_deposit' => $record->can_deposit,
            'can_exchange' => $record->can_exchange,
            'can_transfer' => $record->can_transfer,
            'can_request' => $record->can_request,
            'can_withdraw' => $record->can_withdraw,
            'can_use_voucher' => $record->can_use_voucher,
        ]);
    }

    public function notifyAction(): Action
    {
        return Action::make('notify')
            ->label('')
            ->icon('heroicon-o-bell')
            ->color('primary') 
            ->form([
                Forms\Components\Select::make('channel')
                    ->options([
                        'database' => 'Database Notification',
                        'email' => 'Email',
                    ])
                    ->default('database')
                    ->required(),
                Forms\Components\TextInput::make('subject')
                    ->required(),
                Forms\Components\Textarea::make('message')
                    ->required(),
            ])
            ->action(function (array $data) {
                if ($data['channel'] === 'database') {
                    Notification::make()
                        ->title($data['subject'])
                        ->body($data['message'])
                        ->success()
                        ->sendToDatabase($this->record);
                }
                
                // Email logic would go here
                
                $this->notify('Notification sent successfully.');
            });
    }

    public function walletAction(): Action
    {
        return Action::make('wallet')
            ->label('')
            ->icon('heroicon-o-wallet')
            ->color('success')
            ->form([
                Forms\Components\Select::make('account_id')
                    ->label('Account')
                    ->options(fn () => $this->record->accounts->pluck('account_number', 'id')) // Assuming accounts exist
                    ->required(),
                Forms\Components\Select::make('type')
                    ->options([
                        'credit' => 'Credit (Add Funds)',
                        'debit' => 'Debit (Remove Funds)',
                    ])
                    ->required(),
                Forms\Components\TextInput::make('amount')
                    ->numeric()
                    ->prefix('$')
                    ->required(),
                Forms\Components\Textarea::make('description')
                    ->default('Admin adjustment'),
            ])
            ->action(function (array $data) {
                $account = Account::find($data['account_id']);
                $amount = (float) $data['amount'];
                
                if ($data['type'] === 'credit') {
                    $account->balance += $amount;
                    $account->save();
                    $this->notify("Credited \${$amount} to account.");
                } else {
                    if ($account->balance < $amount) {
                         Notification::make()->title('Insufficient funds')->danger()->send();
                         return;
                    }
                    $account->balance -= $amount;
                    $account->save();
                    $this->notify("Debited \${$amount} from account.");
                }
            });
    }

    public function loginAsUserAction(): Action
    {
        return Action::make('loginAsUser')
            ->label('')
            ->icon('heroicon-o-arrow-right-end-on-rectangle')
            ->color('warning')
            ->requiresConfirmation()
            ->modalHeading('Login as User')
            ->modalDescription('Are you sure you want to login as this user? You will be redirected to their dashboard.')
            ->action(function () {
                Auth::login($this->record);
                return redirect()->to('/dashboard'); // Adjust path as needed
            });
    }

    public function manageCodesAction(): Action
    {
        return Action::make('manageCodes')
            ->label('')
            ->icon('heroicon-o-key')
            ->color('info')
            ->fillForm([
                'imf_code' => $this->record->imf_code,
                'tax_code' => $this->record->tax_code,
                'cot_code' => $this->record->cot_code,
            ])
            ->form([
                Forms\Components\TextInput::make('imf_code')->label('IMF Code'),
                Forms\Components\TextInput::make('tax_code')->label('TAX Code'),
                Forms\Components\TextInput::make('cot_code')->label('COT Code'),
            ])
            ->action(function (array $data) {
                $this->record->update($data);
                $this->notify('Transfer codes updated.');
            });
    }

    public function withdrawalControlAction(): Action
    {
        return Action::make('withdrawalControl')
            ->label('')
            ->icon('heroicon-o-banknotes')
            ->color('danger')
            ->fillForm([
                'withdrawal_status' => $this->record->withdrawal_status,
            ])
            ->form([
                Forms\Components\Select::make('withdrawal_status')
                    ->options([
                        'approved' => 'Approved',
                        'suspended' => 'Suspended',
                        'hold' => 'On Hold',
                        'under_review' => 'Under Review',
                    ])
                    ->required(),
            ])
            ->action(function (array $data) {
                $this->record->update($data);
                $this->notify('Withdrawal status updated.');
            });
    }


    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Group::make()
                    ->schema([
                        Forms\Components\Toggle::make('is_active')
                            ->label('Account Status')
                            ->helperText('Controls user login access.')
                            ->onIcon('heroicon-m-check')
                            ->offIcon('heroicon-m-x-mark')
                            ->onColor('success')
                            ->live()
                            ->afterStateUpdated(function ($state) {
                                $this->record->update(['is_active' => $state]);
                                $this->notify('Account Status updated.');
                            }),
                        
                        Forms\Components\Toggle::make('email_verified')
                            ->label('Email Verification')
                            ->helperText('Requires email verification to activate.')
                            ->onIcon('heroicon-m-check-badge')
                            ->offIcon('heroicon-m-x-circle')
                            ->onColor('success')
                            ->live()
                            ->afterStateUpdated(function ($state) {
                                $this->record->update(['email_verified_at' => $state ? now() : null]);
                                $this->notify('Email Verification updated.');
                            }),

                        Forms\Components\Toggle::make('kyc_verified')
                            ->label('KYC Verification')
                            ->helperText('Requires KYC verification before transactions.')
                            ->onColor('success')
                            ->live()
                            ->afterStateUpdated(function ($state) {
                                $this->record->update(['kyc_verified_at' => $state ? now() : null]);
                                $this->notify('KYC Verification updated.');
                            }),

                        Forms\Components\Toggle::make('can_deposit')
                            ->label('Deposit')
                            ->helperText('Allows users to add funds.')
                            ->live()
                            ->afterStateUpdated(fn ($state) => $this->updatePermission('can_deposit', $state)),
                            
                        Forms\Components\Toggle::make('can_exchange')
                            ->label('Exchange Money')
                            ->helperText('Allows currency conversion.')
                            ->live()
                            ->afterStateUpdated(fn ($state) => $this->updatePermission('can_exchange', $state)),

                        Forms\Components\Toggle::make('can_transfer')
                            ->label('Send Money')
                            ->helperText('Allows sending money to others.')
                            ->live()
                            ->afterStateUpdated(fn ($state) => $this->updatePermission('can_transfer', $state)),

                        Forms\Components\Toggle::make('can_request')
                            ->label('Request Money')
                            ->helperText('Allows requesting money.')
                            ->live()
                            ->afterStateUpdated(fn ($state) => $this->updatePermission('can_request', $state)),

                        Forms\Components\Toggle::make('can_withdraw')
                            ->label('Withdraw')
                            ->helperText('Allows withdrawal to bank.')
                            ->live()
                            ->afterStateUpdated(fn ($state) => $this->updatePermission('can_withdraw', $state)),

                        Forms\Components\Toggle::make('can_use_voucher')
                            ->label('Voucher')
                            ->helperText('Allows voucher usage.')
                            ->live()
                            ->afterStateUpdated(fn ($state) => $this->updatePermission('can_use_voucher', $state)),
                    ])
                    ->columns(1),
            ])
            ->statePath('data');
    }

    protected function updatePermission(string $field, bool $state)
    {
        $this->record->update([$field => $state]);
        $this->notify(str($field)->headline() . ' updated.');
    }

    protected function notify($message)
    {
        Notification::make()
            ->title($message)
            ->success()
            ->send();
    }
    
    public function render()
    {
        return view('livewire.user-controls');
    }
}
