<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Filament\Notifications\Notification as FilamentNotification;

class AdminAlert extends Notification
{
    use Queueable;

    public $title;
    public $message;
    public $url;
    public $type; // 'info', 'success', 'warning', 'danger'

    /**
     * Create a new notification instance.
     */
    public function __construct(string $title, string $message, ?string $url = null, string $type = 'info')
    {
        $this->title = $title;
        $this->message = $message;
        $this->url = $url;
        $this->type = $type;
    }

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject('[Admin Alert] ' . $this->title)
            ->line($this->message);

        if ($this->url) {
            $mail->action('View Details', $this->url);
        }

        return $mail;
    }

    public function toDatabase(object $notifiable): array
    {
        return FilamentNotification::make()
            ->title($this->title)
            ->body($this->message)
            ->status($this->type) // warning, danger, success, info
            ->actions($this->url ? [
                \Filament\Notifications\Actions\Action::make('view')
                    ->label('View')
                    ->url($this->url)
            ] : [])
            ->getDatabaseMessage();
    }
}
