<?php

namespace App\Syllaby\Auth\Mails;

use App\Syllaby\Users\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use App\System\Enums\QueueType;
use App\System\Traits\EmailAssets;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Queue\SerializesModels;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Contracts\Queue\ShouldQueue;

class WelcomeEmail extends Mailable implements ShouldQueue
{
    use EmailAssets, Queueable, SerializesModels;

    const string DEFAULT_VIDEO = 'https://mcusercontent.com/eea40e31c959f76abbd40da79/files/66f11561-3b3e-c541-39c9-1d11b033fab9/lv_0_20230504121402_46SHtJQY.mp4';

    const string DEFAULT_THUMBNAIL = 'welcome-video-cta.png';

    /**
     * Registered user
     */
    public User $user;

    /**
     * Welcome video url
     */
    public string $url;

    /**
     * Welcome video thumbnail image
     */
    public string $thumbnail;

    public function __construct(User $user)
    {
        $this->user = $user;
        $this->url = $this->resolveUrl();
        $this->thumbnail = $this->resolveThumbnail();
        $this->onQueue(QueueType::EMAIL->value);
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Welcome to Syllaby',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.welcome-onboard',
        );
    }

    public function attachments(): array
    {
        return [
            ...$this->defaultAttachments(),
            $this->addEmailAsset('email/banners/base.png', 'banner.png'),
            $this->addEmailAsset("email/shared/{$this->thumbnail}", $this->thumbnail),
        ];
    }

    private function resolveUrl(): string
    {
        $url = $this->user->getFirstMediaUrl('welcome-video');

        return filled($url) ? $url : self::DEFAULT_VIDEO;
    }

    private function resolveThumbnail(): string
    {
        return $this->url === self::DEFAULT_VIDEO ? self::DEFAULT_THUMBNAIL : 'custom-video-cta.png';
    }
}
