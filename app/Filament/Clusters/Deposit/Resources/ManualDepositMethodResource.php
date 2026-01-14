<?php

namespace App\Filament\Clusters\Deposit\Resources;

use App\Filament\Clusters\Deposit;
use App\Filament\Clusters\Deposit\Resources\ManualDepositMethodResource\Pages;
use App\Models\PaymentGateway;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ManualDepositMethodResource extends Resource
{
    protected static ?string $model = PaymentGateway::class;

    protected static ?string $navigationIcon = 'heroicon-o-hand-raised';

    protected static ?string $cluster = Deposit::class;

    protected static ?string $navigationLabel = 'Manual Gateways';

    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Gateway Configuration')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('code')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->label('System Code')
                            ->helperText('Unique identifier like "check_deposit" or "wire_transfer"'),
                        Forms\Components\FileUpload::make('logo')
                            ->image()
                            ->directory('payment-methods')
                            ->label('Gateway Logo')
                            ->columnSpanFull(),
                        Forms\Components\Toggle::make('is_active')
                            ->label('Active')
                            ->default(true),
                    ])->columns(2),

                Forms\Components\Section::make('Instructions')
                    ->schema([
                        Forms\Components\MarkdownEditor::make('instructions')
                            ->label('User Instructions')
                            ->helperText('Instructions shown to the user (e.g., Mail check to address X)'),
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

                Forms\Components\Hidden::make('type')->default('manual'),
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
                Tables\Columns\TextColumn::make('instructions')
                    ->limit(50)
                    ->tooltip(fn ($state) => $state),
                Tables\Columns\TextColumn::make('min_limit')
                    ->money('USD')
                    ->label('Min'),
                Tables\Columns\TextColumn::make('fee_fixed')
                    ->money('USD')
                    ->label('Static Fee'),
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
                ->where('type', 'manual')
            );
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListManualDepositMethods::route('/'),
        ];
    }
}
