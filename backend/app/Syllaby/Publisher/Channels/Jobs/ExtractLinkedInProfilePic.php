<?php

namespace App\Syllaby\Publisher\Channels\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Syllaby\Publisher\Channels\SocialAccount;
use App\Syllaby\Publisher\Channels\SocialChannel;
use App\Syllaby\Assets\Actions\TransloadMediaAction;

class ExtractLinkedInProfilePic implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(protected SocialAccount $account) {}

    public function handle(): void
    {
        $organizations = $this->account->channels()->where('type', SocialChannel::ORGANIZATION)->get();

        $organizations->each(function (SocialChannel $channel) {
            if (! $avatar = $this->extractLogo($channel)) {
                return;
            }

            $media = app(TransloadMediaAction::class)->handle($channel, $avatar, 'avatar');

            $channel->update(['avatar' => $media->getFullUrl()]);
        });
    }

    private function extractLogo(SocialChannel $channel): ?string
    {
        $response = Http::withHeader('X-Restli-Protocol-Version', config('services.linkedin.protocol_version'))
            ->withHeader('LinkedIn-Version', config('services.linkedin.api_version'))
            ->withToken($this->account->access_token)
            ->get("https://api.linkedin.com/v2/organizations/{$channel->provider_id}?projection=(logoV2(original~:playableStreams))");

        if ($response->failed()) {
            Log::alert("Failed to extract LinkedIn profile picture for channel {$channel->id}", [$response->json()]);

            return null;
        }

        return $response->json('logoV2.original~.elements.0.identifiers.0.identifier');
    }
}
