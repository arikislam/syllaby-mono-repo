<?php

namespace App\Http\Controllers\Api\v1\Folders;

use App\Syllaby\Folders\Folder;
use App\Syllaby\Folders\Resource;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Http\Resources\FolderResource;
use Symfony\Component\HttpFoundation\Response;
use App\Http\Resources\FolderWithContentResource;
use App\Http\Requests\Folders\CreateFolderRequest;
use App\Http\Requests\Folders\UpdateFolderRequest;
use App\Syllaby\Folders\Actions\CreateFolderAction;
use App\Syllaby\Folders\Actions\UpdateFolderAction;
use Illuminate\Database\Eloquent\Relations\Relation;

class FolderController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
        $this->middleware('subscribed')->except('index');
    }

    public function index(): JsonResponse
    {
        $user = $this->user();
        $type = Relation::getMorphAlias(Folder::class);

        $resources = Resource::withMaxDepth(10, function () use ($user, $type) {
            return Resource::with('model')->treeOf(function ($query) use ($user, $type) {
                return $query->isRoot()->where('user_id', $user->id)->where('model_type', $type);
            })->where('model_type', $type)->where('user_id', $user->id)->get();
        });

        return $this->respondWithResource(FolderWithContentResource::collection($resources->toTree()));
    }

    public function store(CreateFolderRequest $request, CreateFolderAction $action): JsonResponse
    {
        $folder = $action->handle($this->user(), $request->validated());
        $folder->resource->setRelation('model', $folder);

        return $this->respondWithResource(FolderWithContentResource::make($folder->resource), Response::HTTP_CREATED);
    }

    public function update(UpdateFolderRequest $request, Folder $folder, UpdateFolderAction $action): JsonResponse
    {
        $folder = $action->handle($folder, $request->validated());

        return $this->respondWithResource(FolderResource::make($folder));
    }
}
