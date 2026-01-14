<?php

namespace App\Filament\Clusters\Deposit\Resources;

use App\Filament\Clusters\Deposit;
use App\Filament\Clusters\Deposit\Resources\AutomaticDepositMethodResource\Pages;
use App\Helpers\PaymentLogos;
use App\Models\PaymentGateway;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class AutomaticDepositMethodResource extends Resource
{
    protected static ?string $model = PaymentGateway::class;

    protected static ?string $navigationIcon = 'heroicon-o-bolt';

    protected static ?string $cluster = Deposit::class;

    protected static ?string $navigationLabel = 'Automatic Gateways';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Gateway Configuration')
                    ->schema([
                        Forms\Components\Select::make('code')
                            ->label('Provider')
                            ->options([
                                'stripe' => 'Stripe',
                                'paypal' => 'PayPal',
                                'paystack' => 'Paystack',
                                'flutterwave' => 'Flutterwave',
                                'monnify' => 'Monnify',
                            ])
                            ->required()
                            ->unique(
                                table: PaymentGateway::class,
                                column: 'code',
                                ignoreRecord: true,
                                modifyRuleUsing: fn ($rule) => $rule->where('category', 'deposit')
                            )
                            ->validationMessages([
                                'unique' => 'This provider is already configured for deposits.',
                            ])
                            ->live()
                            ->afterStateUpdated(function (Forms\Set $set, $state) {
                                $names = [
                                    'stripe' => 'Stripe',
                                    'paypal' => 'PayPal',
                                    'paystack' => 'Paystack',
                                    'flutterwave' => 'Flutterwave',
                                    'monnify' => 'Monnify',
                                ];
                                $set('name', $names[$state] ?? null);
                                $set('logo', PaymentLogos::get($state));
                            }),
                            
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->readOnly()
                            ->helperText(''),
                            
                        Forms\Components\Hidden::make('logo'),
                        
                        Forms\Components\Toggle::make('is_active')
                            ->label('Active')
                            ->default(true),
                    ])->columns(2),

                Forms\Components\Section::make('API Configuration')
                    ->schema([
                        // Stripe Fields
                        Forms\Components\Group::make()
                            ->schema([
                                Forms\Components\TextInput::make('credentials.publishable_key')
                                    ->label('Publishable Key')
                                    ->required(),
                                Forms\Components\TextInput::make('credentials.secret_key')
                                    ->label('Secret Key')
                                    ->password()
                                    ->revealable()
                                    ->required(),
                            ])
                            ->visible(fn (Forms\Get $get) => $get('code') === 'stripe'),

                        // PayPal Fields
                        Forms\Components\Group::make()
                            ->schema([
                                Forms\Components\TextInput::make('credentials.client_id')
                                    ->label('Client ID')
                                    ->required(),
                                Forms\Components\TextInput::make('credentials.client_secret')
                                    ->label('Client Secret')
                                    ->password()
                                    ->revealable()
                                    ->required(),
                                Forms\Components\Select::make('credentials.mode')
                                    ->options(['sandbox' => 'Sandbox', 'live' => 'Live'])
                                    ->default('sandbox')
                                    ->required(),
                            ])
                            ->visible(fn (Forms\Get $get) => $get('code') === 'paypal'),

                        // Paystack Fields
                        Forms\Components\Group::make()
                            ->schema([
                                Forms\Components\TextInput::make('credentials.public_key')
                                    ->label('Public Key')
                                    ->required(),
                                Forms\Components\TextInput::make('credentials.secret_key')
                                    ->label('Secret Key')
                                    ->password()
                                    ->revealable()
                                    ->required(),
                            ])
                            ->visible(fn (Forms\Get $get) => $get('code') === 'paystack'),

                         // Flutterwave Fields
                         Forms\Components\Group::make()
                            ->schema([
                                Forms\Components\TextInput::make('credentials.public_key')
                                    ->label('Public Key')
                                    ->required(),
                                Forms\Components\TextInput::make('credentials.secret_key')
                                    ->label('Secret Key')
                                    ->password()
                                    ->revealable()
                                    ->required(),
                                Forms\Components\TextInput::make('credentials.encryption_key')
                                    ->label('Encryption Key')
                                    ->password()
                                    ->revealable()
                                    ->required(),
                            ])
                            ->visible(fn (Forms\Get $get) => $get('code') === 'flutterwave'),
                            
                        // Monnify Fields
                        Forms\Components\Group::make()
                            ->schema([
                                Forms\Components\TextInput::make('credentials.api_key')
                                    ->label('API Key')
                                    ->required(),
                                Forms\Components\TextInput::make('credentials.secret_key')
                                    ->label('Secret Key')
                                    ->password()
                                    ->revealable()
                                    ->required(),
                                Forms\Components\TextInput::make('credentials.contract_code')
                                    ->label('Contract Code')
                                    ->required(),
                            ])
                            ->visible(fn (Forms\Get $get) => $get('code') === 'monnify'),

                    ]),


                Forms\Components\Section::make('Limits & Fees')
                    ->schema([
                        Forms\Components\TextInput::make('min_limit')
                            ->numeric()
                            ->prefix('$')
                            ->default(0),
                        Forms\Components\TextInput::make('max_limit')
                            ->numeric()
                            ->prefix('$')
                            ->nullable(),
                        Forms\Components\TextInput::make('fee_fixed')
                            ->label('Fixed Fee')
                            ->numeric()
                            ->prefix('$')
                            ->default(0),
                        Forms\Components\TextInput::make('fee_percentage')
                            ->label('Percentage Fee')
                            ->numeric()
                            ->suffix('%')
                            ->default(0),
                    ])->columns(2),

                Forms\Components\Hidden::make('type')->default('automatic'),
                Forms\Components\Hidden::make('category')->default('deposit'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Add Gateway')
                    ->modalWidth('2xl'),
            ])
            ->columns([
                Tables\Columns\ImageColumn::make('logo')
                    ->circular(),
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('code')
                    ->searchable()
                    ->fontFamily('mono'),
                Tables\Columns\IconColumn::make('is_active')
                    ->boolean(),
                Tables\Columns\TextColumn::make('min_limit')
                    ->money('USD')
                    ->label('Min'),
                Tables\Columns\TextColumn::make('max_limit')
                    ->money('USD')
                    ->label('Max')
                    ->placeholder('Predefined'),
                Tables\Columns\TextColumn::make('fee_fixed')
                    ->money('USD')
                    ->label('Static Fee'),
                Tables\Columns\TextColumn::make('fee_percentage')
                    ->suffix('%')
                    ->label('% Fee'),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->modifyQueryUsing(fn (Builder $query) => $query
                ->where('category', 'deposit')
                ->where('type', 'automatic')
            );
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAutomaticDepositMethods::route('/'),
        ];
    }
}
