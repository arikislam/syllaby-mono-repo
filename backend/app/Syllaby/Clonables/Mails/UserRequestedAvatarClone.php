<?php

namespace App\Syllaby\Clonables\Mails;

use App\Syllaby\Users\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use App\Syllaby\Clonables\Clonable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Queue\SerializesModels;
use Illuminate\Mail\Mailables\Envelope;

class UserRequestedAvatarClone extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(protected Clonable $clonable, protected User $user)
    {
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Real Clone Request Submitted by {$this->user->name}",
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.avatar-clone.user-request',
            with: [
                'user' => $this->user,
                'details' => $this->clonable->metadata,
                'clone_intent_id' => $this->clonable->id,
            ],
        );
    }

}
