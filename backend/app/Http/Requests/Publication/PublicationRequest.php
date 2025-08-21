<?php

namespace App\Http\Requests\Publication;

use Carbon\Carbon;
use Illuminate\Validation\Validator;
use Illuminate\Support\Facades\Redis;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;
use App\Syllaby\Publisher\Channels\SocialChannel;
use App\Syllaby\Publisher\Publications\Publication;

/**
 * @property-read Publication $publication
 * @property-read SocialChannel $channel
 */
class PublicationRequest extends FormRequest
{
    public Publication $publication;

    public SocialChannel $channel;

    public function prepareForValidation(): void
    {
        $this->publication = Publication::query()->findOr($this->input('publication_id'), function () {
            return $this->makeException('publication_id', 'The selected publication is invalid.');
        });

        $this->channel = SocialChannel::query()->findOr($this->input('channel_id'), function () {
            return $this->makeException('channel_id', 'The selected channel is invalid.');
        });
    }

    /**
     * Get the "after" validation callables for the request.
     */
    public function after(): array
    {
        return [
            function (Validator $validator) {
                [$platform, $limit, $date] = $this->getPlatformAndLimits();

                $key = $this->getCacheKey($platform, $date);

                $expiresAt = Carbon::parse($date)->endOfDay();

                $ttl = max(1, Carbon::now()->diffInSeconds($expiresAt));

                $current = (int) Redis::get($key) ?: 0;

                if ($current >= $limit) {
                    return $this->makeException('message', __('publish.limit_exceeded', ['channel' => $this->channel->name, 'provider' => ucfirst($platform)]))->status(Response::HTTP_TOO_MANY_REQUESTS);
                }

                $this->attributes->set('publications_key', $key);
                $this->attributes->set('publications_expiry', $ttl);
            },
        ];
    }

    /**
     * Extract the platform, limit and scheduled date from the request
     */
    protected function getPlatformAndLimits(): array
    {
        $platform = $this->getPlatformFromPath();

        $scheduledAt = $this->input('scheduled_at');

        $limit = config("publications.limits.{$platform}", PHP_INT_MAX);

        $date = $scheduledAt ? date('Y-m-d', strtotime($scheduledAt)) : now()->format('Y-m-d');

        return [$platform, $limit, $date];
    }

    /**
     * Determine the platform from the request URI
     */
    protected function getPlatformFromPath(): string
    {
        $path = $this->path();

        $segments = explode('/', $path);

        return end($segments);
    }

    /**
     * Get the cache key for tracking publication limits
     */
    protected function getCacheKey(string $platform, string $date): mixed
    {
        $format = config('publications.cache_format');

        return str_replace(
            ['{user}', '{channel}', '{platform}', '{date}'],
            [$this->user()->id, $this->channel->id, $platform, $date],
            $format
        );
    }

    /**
     * Generate a validation exception with a custom message
     */
    protected function makeException(string $attribute, string $message): ValidationException
    {
        throw ValidationException::withMessages([$attribute => $message]);
    }
}
