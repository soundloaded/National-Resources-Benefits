<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\URL;

class VerifyEmailWithCode extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * The verification code.
     */
    public string $code;

    /**
     * Create a new notification instance.
     */
    public function __construct(string $code)
    {
        $this->code = $code;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $verificationUrl = $this->verificationUrl($notifiable);
        $siteName = \App\Models\Setting::get('site_name', 'NationalResourceBenefits');

        return (new MailMessage)
            ->subject('Verify Your Email Address - ' . $siteName)
            ->greeting('Hello ' . ($notifiable->name ?? 'there') . '!')
            ->line('Thank you for registering with ' . $siteName . '. Please verify your email address to activate your account.')
            ->line('**Your verification code is:**')
            ->line('# ' . $this->code)
            ->line('This code will expire in 30 minutes.')
            ->line('Alternatively, you can click the button below to verify your email:')
            ->action('Verify Email Address', $verificationUrl)
            ->line('If you did not create an account, no further action is required.')
            ->salutation('Best regards, ' . $siteName . ' Team');
    }

    /**
     * Get the verification URL for the given notifiable.
     */
    protected function verificationUrl(object $notifiable): string
    {
        return URL::temporarySignedRoute(
            'verification.verify',
            Carbon::now()->addMinutes(Config::get('auth.verification.expire', 60)),
            [
                'id' => $notifiable->getKey(),
                'hash' => sha1($notifiable->getEmailForVerification()),
            ]
        );
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'code' => $this->code,
            'type' => 'email_verification',
        ];
    }
}
