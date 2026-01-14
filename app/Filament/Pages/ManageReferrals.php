<?php

namespace App\Filament\Pages;

use App\Models\ReferralSetting;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Actions\Action;

class ManageReferrals extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-adjustments-horizontal'; // Changed icon
    protected static ?string $navigationGroup = 'User Management';
    protected static ?string $navigationLabel = 'Settings';
    protected static ?string $title = 'Referrals';
    protected ?string $subheading = 'Easily manage and customize referral rewards, levels, and settings below.';
    protected static string $view = 'filament.pages.manage-referrals';
    protected static ?int $navigationSort = 4; // After the Resource

    public ?array $data = [];

    public function mount(): void
    {
        $settings = ReferralSetting::firstOrCreate([
            'id' => 1
        ]);
        
        $this->form->fill($settings->toArray());
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Deposit Rewards')
                    ->description('Manage and customize reward levels below.')
                    ->schema([
                        Toggle::make('deposit_enabled')
                            ->label('Enable Deposit Rewards')
                            ->helperText('Turn on/off deposit rewards.')
                            ->hintIcon('heroicon-m-question-mark-circle', tooltip: 'Toggle this to enable or disable rewards for deposits.')
                            ->columnSpanFull(),
                            
                        Repeater::make('deposit_levels')
                            ->label('Reward Levels')
                            ->schema([
                                TextInput::make('level')
                                    ->numeric()
                                    ->required()
                                    ->label('Level')
                                    ->default(fn ($get) => count($get('../../deposit_levels') ?? []) + 1), // Auto-increment attempt
                                TextInput::make('commission')
                                    ->label('Commission (%)')
                                    ->numeric()
                                    ->required()
                                    ->suffix('%'),
                            ])
                            ->columns(2)
                            ->itemLabel(fn (array $state): ?string => 'Level ' . ($state['level'] ?? '?'))
                            ->reorderableWithButtons()
                            ->collapsible()
                            ->columnSpanFull(),
                    ])
                    ->columnSpan(1),

                Section::make('Payment Rewards')
                    ->description('Manage and customize reward levels below.')
                    ->schema([
                        Toggle::make('payment_enabled')
                            ->label('Enable Payment Rewards')
                            ->helperText('Turn on/off payment rewards.')
                            ->hintIcon('heroicon-m-question-mark-circle', tooltip: 'Toggle this to enable or disable rewards for payments.')
                            ->columnSpanFull(),

                        Repeater::make('payment_levels')
                            ->label('Reward Levels')
                            ->schema([
                                TextInput::make('level')
                                    ->numeric()
                                    ->required()
                                    ->label('Level'),
                                TextInput::make('commission')
                                    ->label('Commission (%)')
                                    ->numeric()
                                    ->required()
                                    ->suffix('%'),
                            ])
                            ->columns(2)
                            ->itemLabel(fn (array $state): ?string => 'Level ' . ($state['level'] ?? '?'))
                            ->reorderableWithButtons()
                            ->collapsible()
                            ->columnSpanFull(),
                    ])
                    ->columnSpan(1),
            ])
            ->statePath('data')
            ->columns(2);
    }
    
    protected function getFormActions(): array
    {
        return [
            Action::make('save')
                ->label('Save changes')
                ->submit('save'),
        ];
    }
    
    public function save(): void
    {
        $data = $this->form->getState();
        
        $settings = ReferralSetting::firstOrNew(['id' => 1]);
        $settings->fill($data);
        $settings->save();
        
        Notification::make()
            ->title('Settings saved successfully')
            ->success()
            ->send();
    }
}
