<?php

namespace App\Http\Controllers\Api\v1\Folders;

use Throwable;
use App\Syllaby\Folders\Folder;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Http\Resources\FolderResource;
use App\Syllaby\Bookmarks\Actions\ToggleBookmarkAction;

class BookmarkFolderController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    /**
     * Update the bookmark status of a folder.
     */
    public function update(Folder $folder, ToggleBookmarkAction $bookmarks): JsonResponse
    {
        $this->authorize('update', $folder);

        try {
            $folder = $bookmarks->handle($this->user(), $folder);
        } catch (Throwable $exception) {
            return $this->errorInternalError($exception->getMessage());
        }

        return $this->respondWithResource(FolderResource::make($folder));
    }
}
