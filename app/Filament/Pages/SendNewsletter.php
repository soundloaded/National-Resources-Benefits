<?php

namespace App\Filament\Pages;

use App\Mail\UserNewsletter;
use App\Models\User;
use Filament\Pages\Page;
use Filament\Tables; 
use Filament\Tables\Table;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Concerns\InteractsWithTable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Mail;
use Filament\Forms; 

class SendNewsletter extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-envelope';
    protected static ?string $navigationGroup = 'Support Center';
    protected static ?string $navigationLabel = 'Email Newsletter';
    protected static ?int $navigationSort = 3;
    protected static string $view = 'filament.pages.send-newsletter';

    public function getTitle(): string
    {
        return 'Email Newsletter';
    }

    public function getSubheading(): ?string
    {
        return 'Send Markdown-based email newsletters to users.';
    }

    public function table(Table $table): Table
    {
        return $table
            ->query($this->getTableQuery())
            ->columns([
                Tables\Columns\TextColumn::make('name')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('email')->searchable(),
                Tables\Columns\TextColumn::make('created_at')->dateTime()->sortable(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('email_verified_at')
                    ->label('Email Verified')
                    ->trueLabel('Verified')
                    ->falseLabel('Unverified')
                    ->placeholder('All'),
            ])
            ->actions([
                Tables\Actions\Action::make('send')
                    ->label('Send')
                    ->icon('heroicon-o-paper-airplane')
                    ->modalHeading('Send Email Newsletter')
                    ->modalWidth('md')
                    ->form([
                        Forms\Components\TextInput::make('subject')
                            ->label('Subject')
                            ->required()
                            ->maxLength(180),
                        Forms\Components\MarkdownEditor::make('body')
                            ->label('Message (Markdown)')
                            ->required()
                            ->columnSpanFull(),
                        Forms\Components\Checkbox::make('send_copy')
                            ->label('Send me a copy')
                            ->default(false),
                    ])
                    ->action(function (array $data, User $record) {
                        Mail::to($record->email)->send(new UserNewsletter($record, $data['subject'], $data['body']));

                        if (!empty($data['send_copy'])) {
                            $admin = auth()->user();
                            if ($admin && $admin->email) {
                                Mail::to($admin->email)->send(new UserNewsletter($record, '[Copy] ' . $data['subject'], $data['body']));
                            }
                        }

                        \Filament\Notifications\Notification::make()
                            ->title('Newsletter sent to ' . $record->name)
                            ->success()
                            ->send();
                    }),
            ])
            ->bulkActions([]);
    }

    protected function getTableQuery(): Builder
    {
        return User::query()
            ->whereDoesntHave('roles', function ($q) {
                $q->where('name', 'like', '%admin%');
            });
    }
}
