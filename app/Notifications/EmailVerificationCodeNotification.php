<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class EmailVerificationCodeNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $code,
        public string $verificationUrl
    ) {}

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Verify Your Email Address')
            ->greeting('Hello ' . $notifiable->name . '!')
            ->line('Thank you for registering with ' . config('app.name') . '.')
            ->line('Your email verification code is:')
            ->line('**' . $this->code . '**')
            ->line('This code will expire in 30 minutes.')
            ->line('Alternatively, you can click the button below to verify your email:')
            ->action('Verify Email Address', $this->verificationUrl)
            ->line('If you did not create an account, no further action is required.');
    }
}
