<?php

namespace App\Http\Controllers\Api\v1\Folders;

use Illuminate\Http\Request;
use App\Syllaby\Folders\Folder;
use App\Syllaby\Folders\Resource;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use Symfony\Component\HttpFoundation\Response;
use App\Http\Resources\FolderWithContentResource;
use App\Http\Requests\Folders\DeleteFolderRequest;
use App\Http\Requests\Folders\MoveResourceRequest;
use App\Syllaby\Folders\Actions\DeleteFolderAction;
use App\Syllaby\Folders\Actions\FetchFoldersAction;
use App\Syllaby\Folders\Actions\MoveToFolderAction;
use Illuminate\Database\Eloquent\Relations\Relation;

class ResourceController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    public function index(Request $request, FetchFoldersAction $fetch): JsonResponse
    {
        $user = $this->user();
        $resource = $this->root($request->query('id'));

        $collection = $fetch->handle($resource, $user, $request->query());

        return $this->respondWithPagination(FolderWithContentResource::collection($collection)->additional([
            'parent_id' => $resource->id,
            'breadcrumbs' => [...$this->breadcrumbs($resource)],
        ]));
    }

    public function move(MoveResourceRequest $request, Resource $destination, MoveToFolderAction $move): JsonResponse
    {
        $destination = $move->handle($destination, $request->validated('resources'));

        $destination->refresh()->loadMissing('children');

        return $this->respondWithResource(FolderWithContentResource::make($destination));
    }

    public function destroy(DeleteFolderRequest $request, DeleteFolderAction $action): Response
    {
        $action->handle($this->user(), $request->validated('resources'), $request->validated('delete_unused_assets', false));

        return response()->noContent();
    }

    /**
     * Gets the root folder for the user.
     */
    private function root(?string $id = null): ?Resource
    {
        $query = Resource::where('user_id', $this->user()->id)
            ->where('model_type', Relation::getMorphAlias(Folder::class));

        return blank($id)
            ? $query->whereNull('parent_id')->first()
            : $query->where('id', $id)->first();
    }

    /**
     * Gets the breadcrumbs for the resource.
     */
    private function breadcrumbs(Resource $resource): array
    {
        return Resource::withInitialQueryConstraint(function ($query) {
            $query->where('user_id', $this->user()->id)
                ->where('model_type', Relation::getMorphAlias(Folder::class));
        }, function () use ($resource) {
            return Resource::withMaxDepth(1, function () use ($resource) {
                $resource->load(['ancestors' => fn ($q) => $q->with('model')->oldest('id')]);

                return $resource->ancestors->map(fn ($ancestor) => [
                    'id' => $ancestor->id, 'name' => $ancestor->model->name,
                ])->push(['id' => $resource->id, 'name' => $resource->model->name])->toArray();
            });
        });
    }
}
