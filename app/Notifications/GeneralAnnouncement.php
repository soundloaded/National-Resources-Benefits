<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class GeneralAnnouncement extends Notification
{
    use Queueable;

    public $title;
    public $body;
    public $channels;

    /**
     * Create a new notification instance.
     */
    public function __construct($title, $body, $channels = ['database'])
    {
        $this->title = $title;
        $this->body = $body;
        $this->channels = $channels;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return $this->channels;
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject($this->title)
            ->greeting('Hello ' . $notifiable->name . ',')
            ->line($this->body)
            ->action('View Dashboard', url('/dashboard'))
            ->line('Thank you for being a valued member associated with National Resource Benefits.');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => $this->title,
            'body' => $this->body,
            'status' => 'info',
        ];
    }
}
