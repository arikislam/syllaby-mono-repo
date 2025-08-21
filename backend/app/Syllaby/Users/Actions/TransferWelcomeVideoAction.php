<?php

namespace App\Syllaby\Users\Actions;

use Log;
use Exception;
use App\Syllaby\Users\User;
use Spatie\MediaLibrary\MediaCollections\Exceptions\FileCannotBeAdded;

class TransferWelcomeVideoAction
{
    protected array $headers = [
        'ACL' => 'public-read',
    ];

    public function handle(User $user, string $url): bool
    {
        try {
            $user->addMediaFromUrl($url)->addCustomHeaders($this->headers)->toMediaCollection('welcome-video');

            return true;
        } catch (FileCannotBeAdded|Exception $error) {
            Log::debug('Transferring Welcome video failed for user {id}', [
                'id' => $user->id,
                'reason' => $error->getMessage(),
            ]);

            return false;
        }
    }
}
