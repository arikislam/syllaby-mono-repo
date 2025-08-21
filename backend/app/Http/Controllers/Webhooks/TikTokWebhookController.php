<?php

namespace App\Http\Controllers\Webhooks;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Syllaby\Publisher\Channels\SocialChannel;
use App\Syllaby\Publisher\Publications\AccountPublication;
use App\Syllaby\Publisher\Publications\Jobs\LogPublicationsJob;
use App\Syllaby\Publisher\Publications\Enums\SocialUploadStatus;
use App\Syllaby\Publisher\Publications\Notifications\PublicationSuccessful;

class TikTokWebhookController
{
    /*
    *   {
    *     "client_key": "fuckfsafvpfsdkux",
    *     "event": "post.publish.complete",
    *     "create_time": 1690692732,
    *     "user_openid": "_000n3xads4rfwdUKrs9LV",
    *     "content": "{\"publish_id\":\"v_pub_url~v2-1.7261463769757501445\",\"publish_type\":\"DIRECT_PUBLISH\"}"
    *   }
    */
    public function handle(Request $request)
    {
        if (! app()->isProduction() && ! app()->runningUnitTests()) {
            return response()->json();
        }

        $id = $request->input('user_openid');
        $content = json_decode($request->input('content'), true);

        if (! $channel = SocialChannel::where('provider_id', $id)->first()) {
            Log::alert('Non-existent user found in tiktok webhook', [$request->all()]);

            return response()->json();
        }

        match ($request->input('event')) {
            'post.publish.failed' => $this->failed($content),
            'post.publish.complete' => $this->complete($content),
            'authorization.removed' => $this->removed($channel, $content),
            'post.publish.publicly_available' => $this->setVideoId($content),
            'post.publish.no_longer_publicly_available' => $this->update($content),
            default => Log::alert('Unhandled TikTok webhook event', [$request->all()])
        };

        return response()->json();
    }

    private function reason(int $code): string
    {
        return match ($code) {
            0 => 'Unknown error',
            1 => 'User disconnects from TikTok app',
            2 => "User's account got deleted",
            3 => "User's age changed",
            4 => "User's account got banned",
            5 => 'Developer revoke authorization'
        };
    }

    public function complete(mixed $content): void
    {
        if (! $publication = AccountPublication::where('metadata->publish_id', data_get($content, 'publish_id'))->first()) {
            return;
        }

        $publication->update(['status' => SocialUploadStatus::COMPLETED->value]);
        $publication->channel->user->notify(new PublicationSuccessful($publication->publication, $publication->channel));

        dispatch(new LogPublicationsJob($publication->publication, $publication->channel, $content));
    }

    public function failed(mixed $content): void
    {
        if (! $publication = AccountPublication::where('metadata->publish_id', data_get($content, 'publish_id'))->first()) {
            return;
        }

        $publication->update(['status' => SocialUploadStatus::FAILED->value, 'error_message' => data_get($content, 'reason')]);

        dispatch(new LogPublicationsJob($publication->publication, $publication->channel, $content));
    }

    public function removed(SocialChannel $channel, mixed $content): void
    {
        $channel->account->setNeedsReauth(true);
        $channel->account->errors = ['tiktok' => ['message' => $this->reason($content['reason'])]];
        $channel->push();
    }

    private function setVideoId(mixed $content)
    {
        if (! $publication = AccountPublication::where('metadata->publish_id', data_get($content, 'publish_id'))->first()) {
            return;
        }

        $publication->update(['provider_media_id' => data_get($content, 'post_id')]);
    }

    private function update(mixed $content)
    {
        if (! $publication = AccountPublication::where('provider_media_id', data_get($content, 'post_id'))->first()) {
            return;
        }

        $publication->update(['status' => SocialUploadStatus::REMOVED_BY_USER->value, 'provider_media_id' => null]);
    }
}
