<?php

namespace App\Syllaby\Ideas\Services;

use Exception;
use App\Syllaby\Users\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use App\Syllaby\Ideas\Keyword;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use App\Syllaby\Ideas\Enums\Networks;
use App\Syllaby\Ideas\Contracts\IdeaDiscovery;
use App\Syllaby\Ideas\Services\Networks\BingNetwork;
use App\Syllaby\Ideas\Services\Networks\GoogleNetwork;
use App\Syllaby\Ideas\Services\Networks\OpenAiNetwork;
use App\Syllaby\Ideas\Services\Networks\TiktokNetwork;
use App\Syllaby\Ideas\Services\Networks\TwitterNetwork;
use App\Syllaby\Ideas\Services\Networks\YoutubeNetwork;
use App\Syllaby\Ideas\Services\Networks\NetworkSearcher;
use App\Syllaby\Ideas\Services\Networks\InstagramNetwork;
use App\Syllaby\Ideas\Services\Networks\PinterestNetwork;
use App\Syllaby\Ideas\Services\Networks\GoogleTrendNetwork;

class KeywordTool implements IdeaDiscovery
{
    /**
     * @throws Exception
     */
    public function search(string $keyword, array $input, User $user): Keyword
    {
        $network = Networks::from(Arr::get($input, 'network'));

        if (! $this->canPerformSearch($user)) {
            $keyword = $this->record($keyword, $network, 'openai');

            return $this->fallback($keyword);
        }

        $keyword = $this->record($keyword, $network, 'keywordtool');

        try {
            $this->resolve($network)->handle($keyword);
        } catch (Exception $exception) {
            Log::error($exception->getMessage());
        }

        return $keyword;
    }

    /**
     * Creates a new searched keyword record or updates it if considered "old".
     */
    private function record(string $keyword, Networks $network, string $source): Keyword
    {
        return Keyword::updateOrCreate(
            attributes: ['slug' => Str::slug($keyword), 'network' => $network],
            values: ['name' => $keyword, 'source' => $source, 'updated_at' => now()]
        );
    }

    /**
     * Decides which KeywordTool network provider to use.
     *
     * @mixin NetworkSearcher
     *
     * @throws Exception
     */
    private function resolve(Networks $network): NetworkSearcher
    {
        return match ($network) {
            Networks::GOOGLE => new GoogleNetwork,
            Networks::GOOGLE_TRENDS => new GoogleTrendNetwork,
            Networks::YOUTUBE => new YoutubeNetwork,
            Networks::INSTAGRAM => new InstagramNetwork,
            Networks::TWITTER => new TwitterNetwork,
            Networks::BING => new BingNetwork,
            Networks::PINTEREST => new PinterestNetwork,
            Networks::TIKTOK => new TiktokNetwork,
        };
    }

    /**
     * Check if there's enough requests left to perform the search.
     */
    private function canPerformSearch(User $user): bool
    {
        if ($user->onTrial() && ! $user->isAdmin()) {
            return false;
        }

        $response = Http::asJson()->timeout(120)->post(config('services.keywordtool.url').'/quota', [
            'apikey' => config('services.keywordtool.key'),
        ]);

        if ($response->failed()) {
            return false;
        }

        if ($response->json('limits.minute.remaining') < 2) {
            return false;
        }

        if ($response->json('limits.daily.remaining') < 2) {
            return false;
        }

        return true;
    }

    /**
     * Fallback strategy when it is not possible to user Keywordtool.
     *
     * @throws Exception
     */
    private function fallback(Keyword $keyword): Keyword
    {
        return tap($keyword, fn () => app(OpenAiNetwork::class)->handle($keyword));
    }
}
