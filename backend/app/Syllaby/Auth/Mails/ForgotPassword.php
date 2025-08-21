<?php

namespace App\Syllaby\Auth\Mails;

use App\Syllaby\Users\User;
use Illuminate\Support\Arr;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use App\System\Traits\EmailAssets;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Queue\SerializesModels;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Contracts\Queue\ShouldQueue;

class ForgotPassword extends Mailable implements ShouldQueue
{
    use EmailAssets, Queueable, SerializesModels;

    public User $user;

    public string $url;

    /**
     * Create a new message instance.
     */
    public function __construct(User $user, string $token)
    {
        $this->user = $user;
        $this->url = $this->resetPasswordUrl($user, $token);
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Password reset request for your syllaby account',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'emails.forgot-password',
        );
    }

    /**
     * Get the attachments for the message.
     */
    public function attachments(): array
    {
        $banner = Attachment::fromStorageDisk('assets', 'email/banners/base.png')
            ->as('banner.png')
            ->withMime('image/png');

        return [...$this->defaultAttachments(), $banner];
    }

    /**
     * Build the frontend reset password url.
     */
    private function resetPasswordUrl(User $user, string $token): string
    {
        $query = Arr::query(['token' => $token, 'email' => $user->email]);

        return config('app.frontend_url')."/reset-password?{$query}";
    }
}
