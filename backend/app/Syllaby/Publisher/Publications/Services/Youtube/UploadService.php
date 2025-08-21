<?php

namespace App\Syllaby\Publisher\Publications\Services\Youtube;

use Exception;
use Throwable;
use Google\Client;
use RuntimeException;
use Google\Service\YouTube;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use App\Syllaby\Assets\Media;
use Google\Http\MediaFileUpload;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use App\Syllaby\Publisher\Channels\SocialChannel;
use App\Syllaby\Publisher\Publications\Publication;
use Google\Service\Exception as GoogleServiceException;
use App\Syllaby\Publisher\Channels\Enums\SocialAccountEnum;
use App\Syllaby\Publisher\Publications\DTOs\YoutubeVideoData;
use App\Syllaby\Publisher\Publications\Enums\SocialUploadStatus;

class UploadService
{
    const int CHUNK_SIZE = 8 * 1024 * 1024;

    const array RETRIABLE_STATUS_CODES = [500, 502, 503, 504];

    protected Client $client;

    protected YouTube $youtube;

    protected YouTube\VideoSnippet $snippet;

    protected YouTube\VideoStatus $status;

    protected YouTube\Video $video;

    protected YoutubeVideoData $dto;

    public function __construct()
    {
        $this->client = new Client;
        $this->client->setDeveloperKey(config('services.youtube.developer_key'));
    }

    /** @throws Throwable */
    public function upload(Publication $publication, SocialChannel $channel): array
    {
        $this->client->setAccessToken($channel->account->access_token);

        $this->youtube = new YouTube($this->client);
        $this->snippet = new YouTube\VideoSnippet;
        $this->status = new YouTube\VideoStatus;
        $this->video = new YouTube\Video;

        $this->setVideoParams($publication, $channel);

        try {
            $this->client->setDefer(true);
            $status = $this->processVideo($publication);
            $this->client->setDefer(false);
        } catch (GoogleServiceException $exception) {
            return ['status' => 'failed', 'errors' => $exception->getErrors()];
        } catch (Throwable $exception) {
            return ['status' => 'failed', 'errors' => [['message' => $exception->getMessage()]]];
        }

        if ($this->successful($status)) {
            $this->setThumbnail($publication, $channel, $status);
        }

        return ['status' => 'success', 'response' => $status];
    }

    public function successful(mixed $status): bool
    {
        return Arr::get($status, 'status.uploadStatus') === 'uploaded';
    }

    public function getDto(Publication $publication, SocialChannel $channel): YoutubeVideoData
    {
        $data = $publication->channels()->wherePivot('social_channel_id', $channel->id)->first();

        return YoutubeVideoData::fromArray($data->pivot->metadata);
    }

    private function setVideoParams(Publication $publication, SocialChannel $channel): void
    {
        $this->dto = $this->getDto($publication, $channel);

        $this->snippet->setTitle($this->dto->title);
        $this->snippet->setDescription($this->dto->description);
        $this->snippet->setTags($this->dto->tags);
        $this->snippet->setCategoryId($this->dto->category);

        $this->status->setPrivacyStatus($this->dto->privacy_status);
        $this->status->setMadeForKids($this->dto->made_for_kids);
        $this->status->setEmbeddable($this->dto->embeddable);
        $this->status->setLicense($this->dto->license);

        $this->video->setSnippet($this->snippet);
        $this->video->setStatus($this->status);
    }

    /**
     * Process the video upload and handling the file in chunks.
     *
     * @throws Throwable
     */
    private function processVideo(Publication $publication): mixed
    {
        /** @var YouTube\Resource\Videos $request */
        $request = $this->youtube->videos->insert('status,snippet', $this->video, [
            'notifySubscribers' => $this->dto->notify_subscribers,
        ]);

        if (! $video = $publication->asset()) {
            throw new Exception("Video not found for publication ID: {$publication->id}");
        }

        $path = $this->getTemporaryPath($video);

        $media = new MediaFileUpload($this->client, $request, 'video/*', null, true, self::CHUNK_SIZE);
        $media->setFileSize(Storage::disk('local')->size($path));

        return $this->uploadChunks($media, $path);
    }

    /**
     * @throws Throwable
     */
    private function uploadChunks(MediaFileUpload $media, string $path): mixed
    {
        $status = false;

        if (! $stream = Storage::disk('local')->readStream($path)) {
            throw new RuntimeException('Failed to open media file');
        }

        try {
            while (! $status && ! feof($stream)) {
                if (($chunk = fread($stream, self::CHUNK_SIZE)) === false) {
                    throw new RuntimeException('Failed to read media file chunk');
                }

                $status = retry(10, fn () => $media->nextChunk($chunk), 1000, function (Exception $exception) {
                    return $this->isRetriableError($exception);
                });
            }

            return $status;
        } finally {
            @fclose($stream);
            Storage::disk('local')->deleteDirectory(dirname($path));
        }
    }

    /**
     * Get the temporary path for the video.
     */
    private function getTemporaryPath(Media $video): string
    {
        $uuid = Str::uuid();
        $base = "tmp/uploads/{$uuid}/{$video->getDownloadFilename()}";

        if (Storage::disk('local')->missing(dirname($base))) {
            Storage::disk('local')->makeDirectory(dirname($base));
        }

        $path = Storage::disk('local')->path($base);
        Http::timeout(600)->retry(3, 1000)->sink($path)->get($video->getUrl());

        return $base;
    }

    /**
     * Set the thumbnail for the publication.
     */
    private function setThumbnail(Publication $publication, SocialChannel $channel, mixed $status): void
    {
        if (! $thumbnail = $publication->thumbnail(SocialAccountEnum::Youtube)) {
            return;
        }

        try {
            $this->youtube->thumbnails->set(Arr::get($status, 'id'), $this->thumbnailPayload($thumbnail));
        } catch (GoogleServiceException $exception) {
            $error = Arr::get(json_decode($exception->getMessage(), true), 'error.errors.0');

            if (Arr::get($error, 'domain') == 'youtube.thumbnail') {
                $publication->channels()->updateExistingPivot($channel, [
                    'error_message' => Arr::get($error, 'message'),
                    'status' => SocialUploadStatus::COMPLETED->value,
                ]);
            }
        }
    }

    /**
     * Get the payload for the thumbnail upload.
     */
    private function thumbnailPayload(Media $thumbnail): array
    {
        return [
            'data' => file_get_contents($thumbnail->getUrl()),
            'mimeType' => $thumbnail->mime_type,
            'uploadType' => 'multipart',
        ];
    }

    /**
     * Check if the exception is a retriable error.
     */
    private function isRetriableError(Throwable $exception): bool
    {
        if (! method_exists($exception, 'getCode')) {
            return false;
        }

        return in_array($exception->getCode(), self::RETRIABLE_STATUS_CODES);
    }
}
