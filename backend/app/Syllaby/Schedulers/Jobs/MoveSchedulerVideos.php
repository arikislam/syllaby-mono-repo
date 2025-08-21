<?php

namespace App\Syllaby\Schedulers\Jobs;

use App\Syllaby\Users\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use App\Syllaby\Videos\Video;
use Illuminate\Bus\Batchable;
use App\Syllaby\Folders\Folder;
use App\System\Enums\QueueType;
use App\Syllaby\Folders\Resource;
use App\Syllaby\Schedulers\Scheduler;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use App\Syllaby\Folders\Actions\CreateFolderAction;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Queue\Middleware\SkipIfBatchCancelled;

class MoveSchedulerVideos implements ShouldBeUnique, ShouldQueue
{
    use Batchable, Queueable;

    protected Scheduler $scheduler;

    /**
     * Create a new job instance.
     */
    public function __construct(Scheduler $scheduler)
    {
        $this->onConnection('videos');
        $this->onQueue(QueueType::FACELESS->value);

        $this->scheduler = $scheduler->withoutRelations();
    }

    /**
     * Execute the job.
     */
    public function handle(CreateFolderAction $finder): void
    {
        $user = $this->scheduler->user;

        $destination = Arr::get($this->scheduler->metadata, 'destination');
        $resource = blank($destination) ? $this->createFolder($finder, $user) : Resource::find($destination);

        $this->scheduler->load('videos');
        $this->moveTo($resource, $this->scheduler->videos);

        $metadata = Arr::only($this->scheduler->metadata, ['ai_labels', 'custom_description']);
        $this->scheduler->update(['metadata' => $metadata]);
    }

    /**
     * Get the unique ID for the job.
     */
    public function uniqueId(): string
    {
        return $this->scheduler->id;
    }

    /**
     * Get the middleware the job should pass through.
     */
    public function middleware(): array
    {
        return [new SkipIfBatchCancelled];
    }

    /**
     * Create a folder for the scheduler.
     */
    private function createFolder(CreateFolderAction $finder, User $user): Resource
    {
        $folder = $finder->handle($user, [
            'name' => Str::words($this->scheduler->title, 12, '...'),
            'parent_id' => $this->rootResource($user)->id,
        ]);

        return $folder->resource;
    }

    /**
     * Get the root folder for the user.
     */
    private function rootResource(User $user): Resource
    {
        return Resource::where('user_id', $user->id)
            ->where('model_type', Relation::getMorphAlias(Folder::class))
            ->whereNull('parent_id')
            ->sole();
    }

    /**
     * Move the videos to the folder.
     */
    private function moveTo(Resource $resource, Collection $videos): void
    {
        $attributes = $videos->map(fn (Video $video) => [
            'parent_id' => $resource->id,
            'user_id' => $video->user_id,
            'model_id' => $video->id,
            'model_type' => Relation::getMorphAlias(Video::class),
            'created_at' => now(),
            'updated_at' => now(),
        ])->toArray();

        Resource::insert($attributes);
    }
}
