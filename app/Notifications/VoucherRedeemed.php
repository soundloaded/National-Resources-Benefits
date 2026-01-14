<?php

namespace App\Notifications;

use App\Models\Voucher;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class VoucherRedeemed extends Notification
{
    use Queueable;

    public Voucher $voucher;
    public float $amount;

    /**
     * Create a new notification instance.
     */
    public function __construct(Voucher $voucher, float $amount)
    {
        $this->voucher = $voucher;
        $this->amount = $amount;
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
        $amount = number_format($this->amount, 2);
        
        return [
            'title' => 'Voucher Redeemed',
            'message' => "You successfully redeemed voucher \"{$this->voucher->name}\" for \${$amount}.",
            'icon' => 'pi pi-ticket',
            'color' => 'green',
            'action_url' => '/voucher/history',
            'action_text' => 'View History',
            'voucher_id' => $this->voucher->id,
            'voucher_code' => $this->voucher->code,
            'amount' => $this->amount,
        ];
    }
}
