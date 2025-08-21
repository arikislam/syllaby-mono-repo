<?php

namespace App\Syllaby\Users\Actions;

use Throwable;
use App\Syllaby\Users\Jobs;
use App\Syllaby\Users\User;
use App\Syllaby\Assets\Asset;
use App\System\Enums\QueueType;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;
use App\Syllaby\Users\Notifications\AccountDeletionConfirmation;

class DeleteUserAction
{
    public function handle(User $user): void
    {
        $user->tokens()->delete();

        $user->setInactive();

        $user->notify(new AccountDeletionConfirmation);

        $jobs = [
            new Jobs\DeleteUserSubscription($user),
            new Jobs\DeleteUserVideos($user, true),
            new Jobs\DeleteUserUploads($user),
            new Jobs\DeleteUserPublications($user),
            new Jobs\DeleteUserClones($user),
            new Jobs\DisconnectUserSocialMedia($user),
            new Jobs\DeleteUserDiscoveredIdeas($user),
            new Jobs\DeleteUserSchedulers($user),
            new Jobs\DeleteUserFolders($user),
            new Jobs\UnsubscribeNewsletter($user),
            new Jobs\DeleteTransactionsHistory($user),
            new Jobs\DeleteUserInteractionData($user),
            new Jobs\DeleteUserSecurityAndTransactionalData($user),
        ];

        if (count($assets = $this->getAssetsBatch($user)) > 0) {
            $jobs[] = Bus::batch($assets)->name("assets-deletion-{$user->id}");
        }

        $jobs[] = new Jobs\DeleteUserMedia($user);
        $jobs[] = new Jobs\DeleteUserAccount($user);

        Bus::chain($jobs)->catch(function (Throwable $throwable) {
            Log::error($throwable->getMessage());
        })
            ->onQueue(QueueType::ACCOUNT_DELETION->value)
            ->dispatch();
    }

    private function getAssetsBatch(User $user): array
    {
        $jobs = [];

        Asset::where('user_id', $user->id)->chunkById(100, function ($assets) use (&$jobs, $user) {
            $ids = $assets->pluck('id')->toArray();

            if (! empty($ids)) {
                $jobs[] = new Jobs\DeleteUserAssetsChunk($user, $ids);
            }
        });

        return $jobs;
    }
}
