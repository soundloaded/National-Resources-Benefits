<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Models\KycDocument;

class KycStatusUpdated extends Notification
{
    use Queueable;

    public $document;
    public $status;
    public $reason;

    /**
     * Create a new notification instance.
     */
    public function __construct(KycDocument $document)
    {
        $this->document = $document;
        $this->status = $document->status;
        $this->reason = $document->rejection_reason;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database']; // Send via Email AND Database
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $message = (new MailMessage)
            ->subject('KYC Verification Update: ' . ucfirst($this->status));

        if ($this->status === 'approved') {
            $message->line('Great news! Your identity document has been approved.')
                ->line("Document Type: " . ucfirst(str_replace('_', ' ', $this->document->document_type)))
                ->line('Your account limits have been lifted accordingly.')
                ->action('Go to Dashboard', url('/dashboard'));
        } else {
            $message->error() // Makes the button red
                ->line('Unfortunately, your document could not be verified.')
                ->line("Reason: {$this->reason}")
                ->line('Please review the requirements and upload a clear, valid document.')
                ->action('Re-upload Document', url('/profile'));
        }

        return $message->line('Thank you for choosing National Resource Benefits.');
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'KYC ' . ucfirst($this->status),
            'body' => $this->status === 'approved' 
                ? 'Your document has been verified.' 
                : 'Your document was rejected. Reason: ' . $this->reason,
            'action_url' => '/profile',
        ];
    }
}
