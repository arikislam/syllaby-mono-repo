<?php

namespace App\Http\Controllers\Api\v1\Ideas;

use Throwable;
use App\Syllaby\Ideas\Topic;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Http\Resources\RelatedTopicResource;
use App\Syllaby\Bookmarks\Actions\ToggleBookmarkAction;

class BookmarkTopicController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct()
    {
        $this->middleware(['auth:sanctum', 'subscribed']);
    }

    /**
     * Toggle a bookmark on a topic.
     */
    public function update(Topic $topic, ToggleBookmarkAction $action): JsonResponse
    {
        try {
            $model = $action->handle($this->user(), $topic);
        } catch (Throwable $exception) {
            return $this->errorInternalError($exception->getMessage());
        }

        return $this->respondWithResource(RelatedTopicResource::make($model));
    }
}
