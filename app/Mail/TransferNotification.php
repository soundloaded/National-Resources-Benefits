<?php

namespace App\Mail;

use App\Models\Transfer;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class TransferNotification extends Mailable
{
    use Queueable, SerializesModels;

    public Transfer $transfer;

    /**
     * Create a new message instance.
     */
    public function __construct(Transfer $transfer)
    {
        $this->transfer = $transfer;
    }

    /**
     * Build the message.
     */
    public function build(): self
    {
        return $this->subject('Transfer Notification: ' . ucfirst($this->transfer->type) . ' Transfer')
            ->markdown('emails.transfer-notification', [
                'transfer' => $this->transfer,
                'user' => $this->transfer->user,
            ]);
    }
}
