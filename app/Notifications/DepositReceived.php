<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Models\Transaction;

class DepositReceived extends Notification
{
    use Queueable;

    public $transaction;

    /**
     * Create a new notification instance.
     */
    public function __construct(Transaction $transaction)
    {
        $this->transaction = $transaction;
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Deposit Confirmed: ' . $this->transaction->currency . ' ' . number_format($this->transaction->amount, 2))
            ->greeting('Hello ' . $notifiable->name . ',')
            ->line('Your deposit has been successfully processed.')
            ->line('Amount: ' . $this->transaction->currency . ' ' . number_format($this->transaction->amount, 2))
            ->line('Transaction ID: ' . $this->transaction->reference_number)
            ->line('Your updated balance is available in your dashboard.')
            ->action('View Dashboard', url('/dashboard'))
            ->line('Thank you for banking with National Resource Benefits.');
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Deposit Received',
            'message' => "Your deposit of {$this->transaction->currency} " . number_format($this->transaction->amount, 2) . " has been credited to your account.",
            'body' => "You have received a deposit of {$this->transaction->currency} " . number_format($this->transaction->amount, 2),
            'icon' => 'pi pi-download',
            'color' => 'green',
            'action_url' => '/accounts',
            'action_text' => 'View Account',
            'transaction_id' => $this->transaction->id,
            'amount' => $this->transaction->amount,
            'currency' => $this->transaction->currency,
        ];
    }
}
