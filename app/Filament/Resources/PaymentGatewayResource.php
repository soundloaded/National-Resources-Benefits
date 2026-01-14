<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PaymentGatewayResource\Pages;
use App\Models\PaymentGateway;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PaymentGatewayResource extends Resource
{
    protected static ?string $model = PaymentGateway::class;

    protected static ?string $navigationIcon = 'heroicon-o-credit-card';
    
    protected static ?string $navigationGroup = 'System';
    
    protected static ?string $navigationLabel = 'Payment Gateways';
    
    protected static ?int $navigationSort = 95;
    
    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Tabs::make('Gateway Configuration')
                    ->tabs([
                        // Basic Info Tab
                        Forms\Components\Tabs\Tab::make('Basic Info')
                            ->icon('heroicon-m-information-circle')
                            ->schema([
                                Forms\Components\Section::make('Gateway Identity')
                                    ->schema([
                                        Forms\Components\Grid::make(2)->schema([
                                            Forms\Components\TextInput::make('name')
                                                ->label('Gateway Name')
                                                ->required()
                                                ->maxLength(255)
                                                ->placeholder('e.g., Stripe Card Payments'),
                                                
                                            Forms\Components\TextInput::make('code')
                                                ->label('Unique Code')
                                                ->required()
                                                ->unique(ignoreRecord: true)
                                                ->maxLength(50)
                                                ->placeholder('e.g., stripe_card')
                                                ->helperText('Internal identifier. Use lowercase with underscores.'),
                                        ]),
                                        
                                        Forms\Components\Textarea::make('description')
                                            ->label('Description')
                                            ->rows(2)
                                            ->placeholder('Brief description of this gateway for internal reference'),
                                            
                                        Forms\Components\FileUpload::make('logo')
                                            ->label('Gateway Logo')
                                            ->image()
                                            ->directory('gateways')
                                            ->imagePreviewHeight('60')
                                            ->maxSize(1024),
                                    ]),

                                Forms\Components\Section::make('Gateway Type')
                                    ->schema([
                                        Forms\Components\Grid::make(3)->schema([
                                            Forms\Components\Select::make('type')
                                                ->label('Gateway Type')
                                                ->options(PaymentGateway::TYPES)
                                                ->required()
                                                ->live()
                                                ->default('automatic'),
                                                
                                            Forms\Components\Select::make('category_new')
                                                ->label('Category')
                                                ->options(PaymentGateway::CATEGORIES)
                                                ->required()
                                                ->default('payment')
                                                ->helperText('What is this gateway used for?'),
                                                
                                            Forms\Components\Select::make('provider')
                                                ->label('Payment Provider')
                                                ->options(PaymentGateway::PROVIDERS)
                                                ->visible(fn (Forms\Get $get) => $get('type') === 'automatic')
                                                ->required(fn (Forms\Get $get) => $get('type') === 'automatic'),
                                        ]),
                                        
                                        Forms\Components\Grid::make(2)->schema([
                                            Forms\Components\Select::make('mode')
                                                ->label('Environment Mode')
                                                ->options([
                                                    'test' => 'Test / Sandbox',
                                                    'live' => 'Live / Production',
                                                ])
                                                ->default('test')
                                                ->visible(fn (Forms\Get $get) => $get('type') === 'automatic')
                                                ->helperText('Use Test mode for development, Live for production.'),
                                                
                                            Forms\Components\Toggle::make('is_active')
                                                ->label('Active')
                                                ->default(true)
                                                ->helperText('Enable or disable this gateway'),
                                        ]),
                                    ]),
                            ]),

                        // Limits & Fees Tab
                        Forms\Components\Tabs\Tab::make('Limits & Fees')
                            ->icon('heroicon-m-calculator')
                            ->schema([
                                Forms\Components\Section::make('Transaction Limits')
                                    ->schema([
                                        Forms\Components\Grid::make(2)->schema([
                                            Forms\Components\TextInput::make('min_limit')
                                                ->label('Minimum Amount')
                                                ->numeric()
                                                ->prefix('$')
                                                ->default(1)
                                                ->required(),
                                                
                                            Forms\Components\TextInput::make('max_limit')
                                                ->label('Maximum Amount')
                                                ->numeric()
                                                ->prefix('$')
                                                ->placeholder('Leave empty for no limit')
                                                ->helperText('Set maximum transaction amount'),
                                        ]),
                                    ]),

                                Forms\Components\Section::make('Fee Structure')
                                    ->description('Fees charged to the user for using this gateway')
                                    ->schema([
                                        Forms\Components\Grid::make(2)->schema([
                                            Forms\Components\TextInput::make('fee_percentage')
                                                ->label('Percentage Fee')
                                                ->numeric()
                                                ->suffix('%')
                                                ->default(0)
                                                ->step(0.01)
                                                ->helperText('Fee as percentage of transaction'),
                                                
                                            Forms\Components\TextInput::make('fee_fixed')
                                                ->label('Fixed Fee')
                                                ->numeric()
                                                ->prefix('$')
                                                ->default(0)
                                                ->step(0.01)
                                                ->helperText('Fixed fee per transaction'),
                                        ]),
                                    ]),
                                    
                                Forms\Components\Section::make('Currency Support')
                                    ->schema([
                                        Forms\Components\TagsInput::make('supported_currencies')
                                            ->label('Supported Currencies')
                                            ->placeholder('Add currency codes (e.g., USD, EUR, GBP)')
                                            ->helperText('Leave empty to support all currencies'),
                                    ]),
                            ]),

                        // API Credentials Tab (for automatic gateways)
                        Forms\Components\Tabs\Tab::make('API Credentials')
                            ->icon('heroicon-m-key')
                            ->visible(fn (Forms\Get $get) => $get('type') === 'automatic')
                            ->schema([
                                Forms\Components\Section::make('Test / Sandbox Credentials')
                                    ->description('Use these for development and testing')
                                    ->schema([
                                        Forms\Components\TextInput::make('credentials.test_public_key')
                                            ->label('Test Public Key')
                                            ->password()
                                            ->revealable()
                                            ->placeholder('pk_test_... or similar'),
                                            
                                        Forms\Components\TextInput::make('credentials.test_secret_key')
                                            ->label('Test Secret Key')
                                            ->password()
                                            ->revealable()
                                            ->placeholder('sk_test_... or similar'),
                                            
                                        Forms\Components\TextInput::make('credentials.test_webhook_secret')
                                            ->label('Test Webhook Secret')
                                            ->password()
                                            ->revealable()
                                            ->placeholder('whsec_... or similar'),
                                    ])->columns(1),

                                Forms\Components\Section::make('Live / Production Credentials')
                                    ->description('Use these for live transactions - KEEP SECURE!')
                                    ->schema([
                                        Forms\Components\TextInput::make('credentials.live_public_key')
                                            ->label('Live Public Key')
                                            ->password()
                                            ->revealable()
                                            ->placeholder('pk_live_... or similar'),
                                            
                                        Forms\Components\TextInput::make('credentials.live_secret_key')
                                            ->label('Live Secret Key')
                                            ->password()
                                            ->revealable()
                                            ->placeholder('sk_live_... or similar'),
                                            
                                        Forms\Components\TextInput::make('credentials.live_webhook_secret')
                                            ->label('Live Webhook Secret')
                                            ->password()
                                            ->revealable()
                                            ->placeholder('whsec_... or similar'),
                                    ])->columns(1),
                            ]),

                        // Manual Gateway Details Tab (for manual gateways)
                        Forms\Components\Tabs\Tab::make('Payment Details')
                            ->icon('heroicon-m-document-text')
                            ->visible(fn (Forms\Get $get) => $get('type') === 'manual')
                            ->schema([
                                Forms\Components\Section::make('Bank Transfer Details')
                                    ->description('Enter bank account details for manual transfers')
                                    ->visible(fn (Forms\Get $get) => str_contains(strtolower($get('name') ?? ''), 'bank'))
                                    ->schema([
                                        Forms\Components\TextInput::make('credentials.bank_name')
                                            ->label('Bank Name'),
                                        Forms\Components\TextInput::make('credentials.account_name')
                                            ->label('Account Name'),
                                        Forms\Components\TextInput::make('credentials.account_number')
                                            ->label('Account Number'),
                                        Forms\Components\TextInput::make('credentials.routing_number')
                                            ->label('Routing Number / Sort Code'),
                                        Forms\Components\TextInput::make('credentials.swift_code')
                                            ->label('SWIFT/BIC Code'),
                                        Forms\Components\TextInput::make('credentials.iban')
                                            ->label('IBAN'),
                                    ])->columns(2),

                                Forms\Components\Section::make('Crypto Wallet Details')
                                    ->description('Enter cryptocurrency wallet addresses')
                                    ->visible(fn (Forms\Get $get) => str_contains(strtolower($get('name') ?? ''), 'crypto') || str_contains(strtolower($get('name') ?? ''), 'bitcoin') || str_contains(strtolower($get('name') ?? ''), 'usdt'))
                                    ->schema([
                                        Forms\Components\TextInput::make('credentials.wallet_address')
                                            ->label('Wallet Address')
                                            ->columnSpanFull(),
                                        Forms\Components\TextInput::make('credentials.network')
                                            ->label('Network')
                                            ->placeholder('e.g., ERC-20, TRC-20, BEP-20'),
                                        Forms\Components\FileUpload::make('credentials.qr_code')
                                            ->label('QR Code')
                                            ->image()
                                            ->directory('gateway-qr'),
                                    ])->columns(2),

                                Forms\Components\Section::make('Custom Payment Details')
                                    ->description('For other manual payment methods')
                                    ->schema([
                                        Forms\Components\KeyValue::make('credentials.custom_fields')
                                            ->label('Custom Fields')
                                            ->keyLabel('Field Name')
                                            ->valueLabel('Value')
                                            ->addActionLabel('Add Field')
                                            ->columnSpanFull(),
                                    ]),
                            ]),

                        // Instructions Tab
                        Forms\Components\Tabs\Tab::make('Instructions')
                            ->icon('heroicon-m-document-text')
                            ->schema([
                                Forms\Components\Section::make('User Instructions')
                                    ->description('Instructions shown to users when using this gateway')
                                    ->schema([
                                        Forms\Components\MarkdownEditor::make('instructions')
                                            ->label('Payment Instructions')
                                            ->toolbarButtons([
                                                'bold',
                                                'bulletList',
                                                'orderedList',
                                                'heading',
                                                'link',
                                            ])
                                            ->helperText('Markdown supported. Explain how users should complete payment.')
                                            ->columnSpanFull(),
                                    ]),
                                    
                                Forms\Components\Section::make('Display Settings')
                                    ->schema([
                                        Forms\Components\TextInput::make('sort_order')
                                            ->label('Sort Order')
                                            ->numeric()
                                            ->default(0)
                                            ->helperText('Lower numbers appear first'),
                                    ]),
                            ]),
                    ])
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('logo')
                    ->label('')
                    ->size(40)
                    ->circular(),
                    
                Tables\Columns\TextColumn::make('name')
                    ->label('Gateway')
                    ->searchable()
                    ->sortable()
                    ->description(fn (PaymentGateway $record) => $record->code),
                    
                Tables\Columns\TextColumn::make('type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'automatic' => 'success',
                        'manual' => 'warning',
                        default => 'gray',
                    }),
                    
                Tables\Columns\TextColumn::make('provider')
                    ->badge()
                    ->color('info')
                    ->placeholder('-'),
                    
                Tables\Columns\TextColumn::make('category_new')
                    ->label('Category')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'deposit' => 'success',
                        'withdrawal' => 'warning',
                        'payment' => 'info',
                        default => 'gray',
                    }),
                    
                Tables\Columns\TextColumn::make('mode')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'live' => 'danger',
                        'test' => 'warning',
                        default => 'gray',
                    })
                    ->visible(fn ($record) => $record?->type === 'automatic'),
                    
                Tables\Columns\TextColumn::make('min_limit')
                    ->label('Min')
                    ->money('USD')
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('max_limit')
                    ->label('Max')
                    ->money('USD')
                    ->placeholder('∞')
                    ->sortable(),
                    
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean()
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Updated')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('sort_order')
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->options(PaymentGateway::TYPES),
                Tables\Filters\SelectFilter::make('category_new')
                    ->label('Category')
                    ->options(PaymentGateway::CATEGORIES),
                Tables\Filters\SelectFilter::make('provider')
                    ->options(PaymentGateway::PROVIDERS),
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active Status'),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\Action::make('toggle')
                        ->label(fn (PaymentGateway $record) => $record->is_active ? 'Deactivate' : 'Activate')
                        ->icon(fn (PaymentGateway $record) => $record->is_active ? 'heroicon-o-x-circle' : 'heroicon-o-check-circle')
                        ->color(fn (PaymentGateway $record) => $record->is_active ? 'danger' : 'success')
                        ->requiresConfirmation()
                        ->action(fn (PaymentGateway $record) => $record->update(['is_active' => !$record->is_active])),
                    Tables\Actions\DeleteAction::make(),
                ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\BulkAction::make('activate')
                        ->label('Activate Selected')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->action(fn ($records) => $records->each->update(['is_active' => true])),
                    Tables\Actions\BulkAction::make('deactivate')
                        ->label('Deactivate Selected')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->action(fn ($records) => $records->each->update(['is_active' => false])),
                ]),
            ])
            ->reorderable('sort_order');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPaymentGateways::route('/'),
            'create' => Pages\CreatePaymentGateway::route('/create'),
            'edit' => Pages\EditPaymentGateway::route('/{record}/edit'),
        ];
    }
}
