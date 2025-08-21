<?php

namespace App\Http\Controllers\Api\v1\Subscriptions;

use Throwable;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use Symfony\Component\HttpFoundation\Response;
use App\Http\Requests\Subscriptions\ManageStorageRequest;
use App\Syllaby\Subscriptions\Actions\CancelStorageAction;
use App\Syllaby\Subscriptions\Actions\ManageStorageAction;

class StorageController extends Controller
{
    /**
     * New constructor instance
     */
    public function __construct()
    {
        $this->middleware(['auth:sanctum', 'subscribed']);
    }

    /**
     * Handles the update operation for a specified resource.
     */
    public function update(ManageStorageRequest $request, ManageStorageAction $storage): JsonResponse
    {
        $user = $this->user();

        try {
            $storage->handle($user, $request->validated('quantity'));
        } catch (Throwable $exception) {
            return $this->errorInternalError($exception->getMessage());
        }

        return $this->respondWithMessage('Storage updated successfully');
    }

    /**
     * Removes the specified resource from storage.
     */
    public function destroy(CancelStorageAction $storage): JsonResponse|Response
    {
        $user = $this->user();
        $this->authorize('storage', [$user->plan, 0]);

        try {
            $storage->handle($user);
        } catch (Throwable $exception) {
            return $this->errorInternalError($exception->getMessage());
        }

        return response()->noContent();
    }
}
