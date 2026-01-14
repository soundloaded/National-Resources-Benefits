<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LoginOtpNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * The OTP code.
     */
    public string $otp;

    /**
     * Create a new notification instance.
     */
    public function __construct(string $otp)
    {
        $this->otp = $otp;
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
        $siteName = \App\Models\Setting::get('site_name', 'NationalResourceBenefits');

        return (new MailMessage)
            ->subject('Your Login Verification Code - ' . $siteName)
            ->greeting('Hello ' . ($notifiable->name ?? 'there') . '!')
            ->line('We detected a login attempt to your account. Please use the following code to complete your sign-in:')
            ->line('**Your login verification code is:**')
            ->line('# ' . $this->otp)
            ->line('This code will expire in **10 minutes**.')
            ->line('If you did not attempt to log in, please secure your account immediately by changing your password.')
            ->salutation('Best regards, ' . $siteName . ' Security Team');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'otp' => $this->otp,
            'type' => 'login_otp',
        ];
    }
}
