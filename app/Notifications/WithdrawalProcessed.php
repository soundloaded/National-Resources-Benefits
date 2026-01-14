<?php

namespace App\Notifications;

use App\Models\Transaction;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class WithdrawalProcessed extends Notification
{
    use Queueable;

    public Transaction $transaction;
    public string $status; // 'pending', 'approved', 'completed', 'rejected'

    /**
     * Create a new notification instance.
     */
    public function __construct(Transaction $transaction, string $status = 'pending')
    {
        $this->transaction = $transaction;
        $this->status = $status;
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        $amount = number_format($this->transaction->amount, 2);
        $currency = $this->transaction->currency ?? 'USD';
        
        $titles = [
            'pending' => 'Withdrawal Requested',
            'approved' => 'Withdrawal Approved',
            'completed' => 'Withdrawal Completed',
            'rejected' => 'Withdrawal Rejected',
        ];
        
        $messages = [
            'pending' => "Your withdrawal request for {$currency} {$amount} is being processed.",
            'approved' => "Your withdrawal of {$currency} {$amount} has been approved and is being processed.",
            'completed' => "Your withdrawal of {$currency} {$amount} has been sent to your account.",
            'rejected' => "Your withdrawal request for {$currency} {$amount} was rejected. Please contact support.",
        ];
        
        $colors = [
            'pending' => 'yellow',
            'approved' => 'blue',
            'completed' => 'green',
            'rejected' => 'red',
        ];
        
        return [
            'title' => $titles[$this->status] ?? 'Withdrawal Update',
            'message' => $messages[$this->status] ?? "Your withdrawal has been updated.",
            'icon' => 'pi pi-upload',
            'color' => $colors[$this->status] ?? 'gray',
            'action_url' => '/withdraw/history',
            'action_text' => 'View History',
            'transaction_id' => $this->transaction->id,
            'amount' => $this->transaction->amount,
            'currency' => $currency,
            'status' => $this->status,
        ];
    }
}
