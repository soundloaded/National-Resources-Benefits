<?php

namespace App\Notifications;

use App\Models\Transfer;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TransferCompleted extends Notification
{
    use Queueable;

    public Transfer $transfer;
    public float $amount;
    public string $counterpartyName;
    public string $direction; // 'sent' or 'received'

    /**
     * Create a new notification instance.
     */
    public function __construct(Transfer $transfer, float $amount, string $counterpartyName, string $direction = 'sent')
    {
        $this->transfer = $transfer;
        $this->amount = $amount;
        $this->counterpartyName = $counterpartyName;
        $this->direction = $direction;
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
        $formattedAmount = number_format($this->amount, 2);
        $currency = $this->transfer->currency ?? 'USD';
        
        if ($this->direction === 'received') {
            return [
                'title' => 'Transfer Received',
                'message' => "You received {$currency} {$formattedAmount} from {$this->counterpartyName}.",
                'icon' => 'pi pi-arrow-down',
                'color' => 'green',
                'action_url' => '/accounts',
                'action_text' => 'View Account',
                'transfer_id' => $this->transfer->id,
                'amount' => $this->amount,
                'currency' => $currency,
            ];
        }
        
        return [
            'title' => 'Transfer Sent',
            'message' => "Your transfer of {$currency} {$formattedAmount} to {$this->counterpartyName} has been completed successfully.",
            'icon' => 'pi pi-arrow-up',
            'color' => 'blue',
            'action_url' => '/transfer/history',
            'action_text' => 'View History',
            'transfer_id' => $this->transfer->id,
            'amount' => $this->amount,
            'currency' => $currency,
        ];
    }
}
