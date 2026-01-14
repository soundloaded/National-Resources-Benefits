<?php

namespace App\Filament\Clusters\KycManagement\Resources;

use App\Filament\Clusters\KycManagement;
use App\Filament\Clusters\KycManagement\Resources\KycDocumentResource\Pages;
use App\Filament\Clusters\KycManagement\Resources\KycDocumentResource\RelationManagers;
use App\Models\KycDocument;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

use Filament\Tables\Actions\Action;
use Filament\Notifications\Notification;
use App\Notifications\KycStatusUpdated; // Import Custom Notification

class KycDocumentResource extends Resource
{
    protected static ?string $model = KycDocument::class;

    protected static ?string $navigationIcon = 'heroicon-o-identification';

    protected static ?string $cluster = KycManagement::class;
    
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Document Details')
                    ->schema([
                        Forms\Components\Select::make('user_id')
                            ->relationship('user', 'name')
                            ->disabled()
                            ->required(),
                        Forms\Components\Select::make('document_type')
                            ->options([
                                'passport' => 'Passport',
                                'driver_license' => 'Driver License',
                                'ssn' => 'SSN Card',
                                'custom_form' => 'Custom Form',
                            ])
                            ->disabled()
                            ->visible(fn ($get) => $get('document_type') !== null),
                        Forms\Components\TextInput::make('document_number')
                            ->disabled()
                            ->visible(fn ($get) => $get('document_number') !== null),
                        
                         Forms\Components\Select::make('kyc_template_id')
                            ->relationship('template', 'title')
                            ->disabled()
                            ->visible(fn ($get) => $get('kyc_template_id') !== null)
                            ->label('Submitted Form'),

                        Forms\Components\Select::make('status')
                            ->options([
                                'pending' => 'Pending',
                                'approved' => 'Approved',
                                'rejected' => 'Rejected',
                            ])
                            ->required(),
                        Forms\Components\Textarea::make('rejection_reason')
                            ->columnSpanFull()
                            ->visible(fn ($get) => $get('status') === 'rejected'),
                    ])->columns(2),
                    
                Forms\Components\Section::make('Submitted Data')
                    ->schema([
                        Forms\Components\KeyValue::make('data')
                            ->label('Form Data')
                            ->disabled()
                            ->columnSpanFull(),
                    ])
                    ->visible(fn ($get) => !empty($get('data'))),

                Forms\Components\Section::make('Document Preview')
                    ->schema([
                        Forms\Components\FileUpload::make('document_path')
                            ->label('Document Image')
                            ->image()
                            ->disabled()
                            ->dehydrated(false) // Don't save this field on edit, it's just for display
                            ->columnSpanFull(),
                    ])
                    ->visible(fn ($get) => !empty($get('document_path'))),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('document_type')
                    ->formatStateUsing(fn ($state, KycDocument $record): string => match ($state) {
                        'passport' => 'Passport',
                        'driver_license' => 'Driver License',
                        'ssn' => 'SSN Card',
                        null => $record->template?->title ?? 'Custom Form',
                        default => ucfirst(str_replace('_', ' ', $state)),
                    })
                    ->badge(),
                Tables\Columns\ImageColumn::make('document_path')
                    ->label('Preview')
                    ->disk('public'),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'approved' => 'success',
                        'rejected' => 'danger',
                        'pending' => 'warning',
                    }),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->label('Submitted At'),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'approved' => 'Approved',
                        'rejected' => 'Rejected',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Action::make('approve')
                    ->label('Approve')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (KycDocument $record) => $record->status === 'pending')
                    ->action(function (KycDocument $record) {
                        $record->update([
                            'status' => 'approved',
                            'verified_at' => now(),
                        ]);
                        
                        // Update user's KYC verified status
                        $record->user->update([
                            'kyc_verified_at' => now(),
                        ]);
                        
                        // Notify Admin (Toast)
                        Notification::make()
                            ->title('Document Approved')
                            ->success()
                            ->send();

                        // Notify User (Email + Database)
                        $record->user->notify(new KycStatusUpdated($record));
                    }),
                Action::make('reject')
                    ->label('Reject')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn (KycDocument $record) => $record->status === 'pending')
                    ->form([
                        Forms\Components\Textarea::make('reason')
                            ->label('Rejection Reason')
                            ->required(),
                    ])
                    ->action(function (KycDocument $record, array $data) {
                        $record->update([
                            'status' => 'rejected',
                            'rejection_reason' => $data['reason'],
                        ]);
                        
                        // Notify Admin (Toast)
                        Notification::make()
                            ->title('Document Rejected')
                            ->danger()
                            ->send();

                        // Notify User (Email + Database)
                        $record->user->notify(new KycStatusUpdated($record));
                    }),
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
            'index' => Pages\ListKycDocuments::route('/'),
            'create' => Pages\CreateKycDocument::route('/create'),
            'edit' => Pages\EditKycDocument::route('/{record}/edit'),
        ];
    }
}
