<?php

namespace App\Filament\Clusters\Withdraw\Resources;

use App\Filament\Clusters\Withdraw;
use App\Filament\Clusters\Withdraw\Resources\WithdrawalFormFieldResource\Pages;
use App\Models\WithdrawalFormField;
use App\Models\Setting;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class WithdrawalFormFieldResource extends Resource
{
    protected static ?string $model = WithdrawalFormField::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $cluster = Withdraw::class;

    protected static ?string $navigationLabel = 'Withdrawal Form Fields';

    protected static ?string $modelLabel = 'Form Field';

    protected static ?string $pluralModelLabel = 'Form Fields';

    protected static ?int $navigationSort = 10;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Field Configuration')
                    ->description('Configure the form field that users will fill when linking a withdrawal account')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->label('Field Name (Identifier)')
                            ->helperText('Internal identifier e.g., "bank_name", "account_number", "routing_number"')
                            ->regex('/^[a-z_]+$/')
                            ->validationMessages([
                                'regex' => 'Only lowercase letters and underscores allowed',
                            ]),
                        Forms\Components\TextInput::make('label')
                            ->required()
                            ->maxLength(255)
                            ->label('Display Label')
                            ->helperText('What users will see e.g., "Bank Name", "Account Number"'),
                        Forms\Components\Select::make('type')
                            ->required()
                            ->options(WithdrawalFormField::getFieldTypes())
                            ->default('text')
                            ->reactive(),
                        Forms\Components\TextInput::make('placeholder')
                            ->maxLength(255)
                            ->label('Placeholder Text'),
                    ])->columns(2),

                Forms\Components\Section::make('Select Options')
                    ->description('Define dropdown options (only for Select type)')
                    ->schema([
                        Forms\Components\Repeater::make('options')
                            ->schema([
                                Forms\Components\TextInput::make('label')
                                    ->required()
                                    ->label('Option Label'),
                                Forms\Components\TextInput::make('value')
                                    ->required()
                                    ->label('Option Value'),
                            ])
                            ->columns(2)
                            ->addActionLabel('Add Option')
                            ->reorderableWithButtons(),
                    ])
                    ->visible(fn (Forms\Get $get): bool => $get('type') === 'select'),

                Forms\Components\Section::make('Validation & Help')
                    ->schema([
                        Forms\Components\Toggle::make('is_required')
                            ->label('Required Field')
                            ->default(true),
                        Forms\Components\Toggle::make('is_active')
                            ->label('Active')
                            ->default(true)
                            ->helperText('Inactive fields won\'t be shown to users'),
                        Forms\Components\TextInput::make('sort_order')
                            ->numeric()
                            ->default(0)
                            ->helperText('Lower numbers appear first'),
                        Forms\Components\Textarea::make('help_text')
                            ->label('Help Text')
                            ->helperText('Additional instructions shown below the field')
                            ->rows(2),
                    ])->columns(2),

                Forms\Components\Section::make('Custom Validation Rules')
                    ->description('Add custom validation rules (optional)')
                    ->schema([
                        Forms\Components\TagsInput::make('validation_rules')
                            ->label('Validation Rules')
                            ->helperText('E.g., "min:10", "max:50", "digits:9", "alpha_num"')
                            ->placeholder('Add rule and press Enter'),
                    ])
                    ->collapsed(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('sort_order')
                    ->label('#')
                    ->sortable(),
                Tables\Columns\TextColumn::make('name')
                    ->label('Field Name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('label')
                    ->label('Display Label')
                    ->searchable(),
                Tables\Columns\TextColumn::make('type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'text' => 'gray',
                        'number' => 'info',
                        'email' => 'success',
                        'tel' => 'warning',
                        'select' => 'primary',
                        'textarea' => 'secondary',
                        default => 'gray',
                    }),
                Tables\Columns\IconColumn::make('is_required')
                    ->boolean()
                    ->label('Required'),
                Tables\Columns\IconColumn::make('is_active')
                    ->boolean()
                    ->label('Active'),
            ])
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active Status'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->headerActions([
                Tables\Actions\Action::make('settings')
                    ->label('Account Limit Settings')
                    ->icon('heroicon-o-cog-6-tooth')
                    ->form([
                        Forms\Components\TextInput::make('withdrawal_account_limit')
                            ->label('Max Accounts Per User')
                            ->numeric()
                            ->default(fn () => Setting::get('withdrawal_account_limit', 3))
                            ->helperText('Maximum number of withdrawal accounts each user can create')
                            ->required()
                            ->minValue(1)
                            ->maxValue(10),
                    ])
                    ->action(function (array $data): void {
                        Setting::set('withdrawal_account_limit', $data['withdrawal_account_limit'], 'withdraw', 'integer');
                        \Filament\Notifications\Notification::make()
                            ->title('Settings Updated')
                            ->success()
                            ->send();
                    }),
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
            'index' => Pages\ListWithdrawalFormFields::route('/'),
            'create' => Pages\CreateWithdrawalFormField::route('/create'),
            'edit' => Pages\EditWithdrawalFormField::route('/{record}/edit'),
        ];
    }
}
