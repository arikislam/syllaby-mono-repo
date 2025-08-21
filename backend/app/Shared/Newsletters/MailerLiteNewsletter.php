<?php

namespace App\Shared\Newsletters;

use App\Syllaby\Users\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;

class MailerLiteNewsletter implements Newsletter
{
    private array $groups = [];

    /**
     * Checks whether the user gave permission to be added to a newsletter.
     */
    public function authorize(User $user): bool
    {
        return app()->isProduction() && $user->preferences()->get('mailing_list');
    }

    /**
     * Subscribes a given email.
     */
    public function subscribe(User $user, array $data = []): bool
    {
        if (! $this->authorize($user)) {
            return false;
        }

        return $this->http()->throw()->post('/subscribers', [
            'email' => $user->email,
            'status' => 'active',
            'groups' => $this->groups,
            'fields' => Arr::get($data, 'fields', []),
            'opted_in_at' => now()->format('Y-m-d H:i:s'),
            'subscribed_at' => now()->format('Y-m-d H:i:s'),
        ])->ok();
    }

    /**
     * Unsubscribes a given email.
     */
    public function unsubscribe(User $user): bool
    {
        if (! $this->authorize($user)) {
            return false;
        }
        try {
            $id = Arr::get($this->subscriber($user), 'id');
        } catch (RequestException $exception) {
            return tap(false, fn () => Log::warning($exception->getMessage()));
        }

        return $this->http()->throw()->post("/subscribers/{$id}/forget")->ok();
    }

    /**
     * Update an existing subscriber.
     */
    public function update(User $user, array $data = []): bool
    {
        if (! $this->authorize($user)) {
            return false;
        }

        try {
            $id = Arr::get($this->subscriber($user), 'id');
        } catch (RequestException $exception) {
            return tap(false, fn () => Log::warning($exception->getMessage()));
        }

        return $this->http()->throw()->put("/subscribers/{$id}", [
            'groups' => $this->groups,
            'fields' => Arr::get($data, 'fields', []),
        ])->ok();
    }

    /**
     * Fetch a subscriber with the given email if exists.
     *
     * @throws RequestException
     */
    public function subscriber(User $user): array
    {
        $response = $this->http()->get("/subscribers/{$user->email}");

        if ($response->failed()) {
            $response->throw();
        }

        return $response->json('data');
    }

    /**
     * Groups to be added with the subscriber.
     */
    public function withTags(array $tags): self
    {
        $groups = config('services.mailerlite.groups');

        $tags = collect($tags)->filter(fn ($tag) => Arr::exists($groups, $tag))
            ->map(fn ($tag) => $groups[$tag])
            ->toArray();

        $this->groups = [...$this->groups, ...$tags];

        return $this;
    }

    /**
     * Configure HTTP client to interact with MailerLite API.
     */
    private function http(): PendingRequest
    {
        return Http::acceptJson()
            ->baseUrl(config('services.mailerlite.url'))
            ->withHeaders(['X-Version' => '2024-02-09'])
            ->withToken(config('services.mailerlite.token'));
    }
}
