<?php

namespace App\Syllaby\Videos\Actions;

use Str;
use App\Syllaby\Users\User;
use App\Syllaby\Videos\Video;
use App\Syllaby\Videos\Faceless;
use App\Syllaby\Videos\Enums\Dimension;
use App\Syllaby\Videos\Enums\VideoStatus;
use App\Syllaby\Folders\Actions\AddToRootAction;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class ConvertFacelessAction
{
    public function __construct(protected AddToRootAction $action) {}

    public function handle(Faceless $faceless, User $user)
    {
        $faceless->loadMissing('video');
        $media = $faceless->video->getFirstMedia('video');

        $video = $user->videos()->create([
            'idea_id' => $faceless->video->idea_id,
            'scheduler_id' => $faceless->video->scheduler_id,
            'title' => $faceless->video->title,
            'type' => Video::CUSTOM,
            'status' => VideoStatus::DRAFT,
            'provider' => $faceless->video->provider,
        ]);

        $footage = $video->footage()->create([
            'user_id' => $user->id,
            'preference' => [
                'aspect_ratio' => Dimension::tryFrom($media->getCustomProperty('orientation', 'portrait'))->getAspectRatio(),
            ],
        ]);

        $timeline = $footage->timeline()->create([
            'user_id' => $user->id,
            'provider' => $faceless->video->provider,
            'content' => $this->defaultSource($media, $faceless),
        ]);

        $footage->setRelation('timeline', $timeline);
        $video->setRelation('footage', $footage);

        return tap($video, fn (Video $video) => $this->action->handle($video, $user));
    }

    public function defaultSource(Media $media, Faceless $faceless): array
    {
        return [
            'output_format' => 'mp4',
            'width' => Dimension::fromDuration($faceless->estimated_duration)->get('width'),
            'height' => Dimension::fromDuration($faceless->estimated_duration)->get('height'),
            'frame_rate' => '25 fps',
            'elements' => [
                [
                    'id' => Str::uuid()->toString(),
                    'name' => sprintf('%s_%s_%s', 'video', $media->uuid, (int) $media->getCustomProperty('duration')),
                    'type' => 'video',
                    'time' => 0,
                    'duration' => $media->getCustomProperty('duration'),
                    'source' => $media->getUrl(),
                ],
            ],
        ];
    }
}
