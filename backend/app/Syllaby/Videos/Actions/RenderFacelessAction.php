<?php

namespace App\Syllaby\Videos\Actions;

use Throwable;
use App\Syllaby\Users\User;
use Illuminate\Support\Arr;
use App\Syllaby\Videos\Video;
use InvalidArgumentException;
use App\Syllaby\Videos\Faceless;
use App\Syllaby\Folders\Resource;
use Illuminate\Support\Facades\Bus;
use App\Syllaby\Videos\Enums\VideoStatus;
use App\Syllaby\Videos\Enums\FacelessType;
use App\Syllaby\Folders\Actions\MoveToFolderAction;
use App\Syllaby\Credits\Jobs\ProcessFacelessVideoCharge;
use App\Syllaby\Videos\Jobs\Faceless\ManageFacelessAssets;
use App\Syllaby\Videos\Jobs\Faceless\FindStockFootageClips;
use App\Syllaby\Videos\Jobs\Faceless\TriggerMediaGeneration;
use App\Syllaby\Videos\Jobs\Faceless\BuildFacelessVideoSource;
use App\Syllaby\Videos\Jobs\Faceless\GenerateFacelessVoiceOver;
use App\Syllaby\Videos\Jobs\Faceless\ExtractStockFootageKeywords;

class RenderFacelessAction
{
    public function __construct(protected UpdateFacelessAction $action) {}

    /**
     * @throws Throwable
     */
    public function handle(Faceless $faceless, User $user, array $input): Faceless
    {
        $faceless = $this->action->handle($faceless, $input);

        match ($faceless->type) {
            FacelessType::AI_VISUALS => $this->buildAiVisualsVideo($faceless),
            FacelessType::SINGLE_CLIP => $this->buildSingleClipVideo($faceless),
            FacelessType::B_ROLL => $this->buildBRollVideo($faceless),
            FacelessType::URL_BASED => $this->buildUrlBasedVideo($faceless, $input),
            default => throw new InvalidArgumentException('Invalid faceless type'),
        };

        return attempt(function () use ($faceless, $input) {
            $this->track($faceless);
            $this->markAsRendering($faceless->video, $input);
            $this->move($faceless->video, Arr::get($input, 'destination_id'));

            return $faceless;
        });
    }

    protected function buildUrlBasedVideo(Faceless $faceless, array $input): void
    {
        $assets = Arr::get($input, 'assets', []);

        Bus::chain([
            new GenerateFacelessVoiceOver($faceless),
            new ProcessFacelessVideoCharge($faceless),
            new ManageFacelessAssets($faceless, $assets),
            new BuildFacelessVideoSource($faceless),
        ])->dispatch();
    }

    private function buildSingleClipVideo(Faceless $faceless): void
    {
        Bus::chain([
            new GenerateFacelessVoiceOver($faceless),
            new ProcessFacelessVideoCharge($faceless),
            new BuildFacelessVideoSource($faceless),
        ])->dispatch();
    }

    private function buildAiVisualsVideo(Faceless $faceless): void
    {
        $faceless->assets()->detach();

        Bus::chain([
            new GenerateFacelessVoiceOver($faceless),
            new ProcessFacelessVideoCharge($faceless),
            new TriggerMediaGeneration($faceless),
        ])->dispatch();
    }

    private function buildBRollVideo(Faceless $faceless): void
    {
        $faceless->assets()->detach();

        Bus::chain([
            new GenerateFacelessVoiceOver($faceless),
            new ProcessFacelessVideoCharge($faceless),
            new ExtractStockFootageKeywords($faceless),
            new FindStockFootageClips($faceless),
            new BuildFacelessVideoSource($faceless),
        ])->dispatch();
    }

    /**
     * Initiates the image-regeneration tracker
     */
    private function track(Faceless $faceless): void
    {
        $faceless->trackers()->create([
            'count' => 0,
            'limit' => 3,
            'name' => 'image-generation',
            'user_id' => $faceless->user_id,
        ]);
    }

    /**
     * Set the corresponding video record status as rendering.
     */
    private function markAsRendering(Video $video, array $input): void
    {
        $video->update([
            'title' => Arr::get($input, 'title') ?? $video->title,
            'status' => VideoStatus::RENDERING,
            'synced_at' => null,
            'metadata' => [
                'publications' => Arr::get($input, 'publications'),
                'ai_labels' => Arr::get($input, 'ai_labels', false),
                'custom_description' => Arr::get($input, 'custom_description'),
            ],
        ]);
    }

    /**
     * Move the video to the specified destination.
     */
    private function move(Video $video, ?int $destination): void
    {
        if (blank($destination)) {
            return;
        }

        app(MoveToFolderAction::class)->handle(Resource::find($destination), [$video->resource->id]);
    }
}
