<?php

namespace App\Filament\Resources;

use App\Filament\Resources\NotificationResource\Pages;
use App\Filament\Resources\NotificationResource\RelationManagers;
use App\Models\Notification;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

use App\Models\User;
use Filament\Notifications\Notification as FilamentNotification;
use App\Notifications\GeneralAnnouncement;

class NotificationResource extends Resource
{
    protected static ?string $model = Notification::class;

    protected static ?string $navigationIcon = 'heroicon-o-bell';
    protected static ?string $navigationGroup = 'Financial Management';
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\MorphToSelect::make('notifiable')
                    ->types([
                        Forms\Components\MorphToSelect\Type::make(User::class)
                            ->titleAttribute('name'),
                    ])
                    ->required(),
                Forms\Components\KeyValue::make('data')
                    ->label('Payload'),
                Forms\Components\DateTimePicker::make('read_at'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('notifiable.name')
                    ->label('Recipient')
                    ->searchable(),
                Tables\Columns\TextColumn::make('data.title')
                    ->label('Title')
                    ->searchable(),
                Tables\Columns\TextColumn::make('data.body')
                    ->label('Message')
                    ->limit(50),
                Tables\Columns\TextColumn::make('read_at')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state ? 'Read' : 'Unread')
                    ->color(fn ($state) => $state ? 'success' : 'danger')
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\TernaryFilter::make('read_at')
                    ->label('Read Status')
                    ->placeholder('All Notifications')
                    ->trueLabel('Read')
                    ->falseLabel('Unread')
                    ->queries(
                        true: fn (Builder $query) => $query->whereNotNull('read_at'),
                        false: fn (Builder $query) => $query->whereNull('read_at'),
                    ),
            ])
            ->actions([
                Tables\Actions\DeleteAction::make(),
            ])
            ->headerActions([
                Tables\Actions\Action::make('send_notification')
                    ->label('Send Notification')
                    ->icon('heroicon-o-paper-airplane')
                    ->modalWidth('md')
                    ->form([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Select::make('user_id')
                                    ->label('Recipient')
                                    ->searchable()
                                    ->getSearchResultsUsing(fn (string $search) => User::query()
                                        ->where('name', 'like', "%{$search}%")
                                        ->orWhere('email', 'like', "%{$search}%")
                                        ->limit(25)
                                        ->pluck('name', 'id')
                                        ->toArray())
                                    ->getOptionLabelUsing(fn ($value) => User::find($value)?->name)
                                    ->required(),
                                Forms\Components\TextInput::make('title')
                                    ->required(),
                                Forms\Components\MarkdownEditor::make('body')
                                    ->required()
                                    ->columnSpanFull(),
                                Forms\Components\Select::make('type')
                                    ->options([
                                        'info' => 'Info',
                                        'success' => 'Success',
                                        'warning' => 'Warning',
                                        'danger' => 'Danger',
                                    ])
                                    ->default('info')
                                    ->required(),
                                Forms\Components\Checkbox::make('send_email')
                                    ->label('Send as Email too')
                                    ->default(false)
                                    ->columnSpanFull(),
                            ])
                    ])
                    ->action(function (array $data) {
                        $user = User::find($data['user_id']);
                        
                        $channels = ['database'];
                        if ($data['send_email']) {
                            $channels[] = 'mail';
                        }
                        
                        $user->notify(new GeneralAnnouncement($data['title'], $data['body'], $channels));
                            
                        FilamentNotification::make()
                            ->title('Notification sent successfully')
                            ->success()
                            ->send();
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageNotifications::route('/'),
        ];
    }
}
