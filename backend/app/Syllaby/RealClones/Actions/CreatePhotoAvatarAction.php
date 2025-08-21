<?php

namespace App\Syllaby\RealClones\Actions;

use Exception;
use App\Syllaby\Users\User;
use Illuminate\Support\Arr;
use Illuminate\Http\UploadedFile;
use App\Syllaby\RealClones\Avatar;
use Illuminate\Support\Facades\Log;

class CreatePhotoAvatarAction
{
    /**
     * Create and uploads the user photo avatar.
     */
    public function handle(User $user, string $provider, array $input): Avatar
    {
        return tap($this->persist($user, $provider, $input), function ($avatar) use ($input) {
            $this->upload($avatar, Arr::get($input, 'file'));
        });
    }

    /**
     * Creates an avatar of type photo.
     */
    private function persist(User $user, string $provider, array $input): Avatar
    {
        return Avatar::create([
            'is_active' => true,
            'user_id' => $user->id,
            'provider' => $provider,
            'type' => Avatar::PHOTO,
            'name' => Arr::get($input, 'name'),
            'gender' => Arr::get($input, 'gender'),
            'metadata' => ['face' => Arr::get($input, 'face')],
        ]);
    }

    /**
     * Uploads the avatar photo.
     */
    private function upload(Avatar $avatar, UploadedFile $file): void
    {
        try {
            $media = $avatar->addMedia($file)
                ->setFileName($file->hashName())
                ->addCustomHeaders(['ACL' => 'public-read'])
                ->withAttributes(['user_id' => $avatar->user_id])
                ->toMediaCollection('photo-avatar');

            $avatar->update(['preview_url' => $media->getFullUrl()]);
        } catch (Exception $exception) {
            Log::error('Failed to attach the photo to avatar {reason}', ['reason' => $exception->getMessage()]);
        }
    }
}
