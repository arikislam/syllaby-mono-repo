<?php

namespace App\System\Traits;

use Illuminate\Mail\Mailables\Attachment;

trait EmailAssets
{
    protected array $assets = [
        ['path' => 'email/logo-header.png', 'label' => 'logo-header.png', 'mime' => 'image/png'],
        ['path' => 'email/logo-footer.png', 'label' => 'logo-footer.png', 'mime' => 'image/png'],
        ['path' => 'email/icons/twitter-icon.png', 'label' => 'twitter-icon.png', 'mime' => 'image/png'],
        ['path' => 'email/icons/facebook-icon.png', 'label' => 'facebook-icon.png', 'mime' => 'image/png'],
        ['path' => 'email/icons/instagram-icon.png', 'label' => 'instagram-icon.png', 'mime' => 'image/png'],
    ];

    /**
     * Get the default attachments for the email.
     */
    protected function defaultAttachments(): array
    {
        return collect($this->assets)->map(function ($asset) {
            return $this->addEmailAsset($asset['path'], $asset['label'], $asset['mime']);
        })->toArray();
    }

    /**
     * Add an email asset to the email.
     */
    protected function addEmailAsset(string $path, string $alias, string $mime = 'image/png'): Attachment
    {
        return Attachment::fromStorageDisk('assets', $path)->as($alias)->withMime($mime);
    }
}
