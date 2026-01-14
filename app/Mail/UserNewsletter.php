<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class UserNewsletter extends Mailable
{
    use Queueable, SerializesModels;

    public User $user;
    public string $subjectLine;
    public string $markdownBody;

    /**
     * Create a new message instance.
     */
    public function __construct(User $user, string $subjectLine, string $markdownBody)
    {
        $this->user = $user;
        $this->subjectLine = $subjectLine;
        $this->markdownBody = $markdownBody;
    }

    /**
     * Build the message.
     */
    public function build(): self
    {
        return $this->subject($this->subjectLine)
            ->markdown('emails.newsletter', [
                'user' => $this->user,
                'body' => $this->markdownBody,
            ]);
    }
}
