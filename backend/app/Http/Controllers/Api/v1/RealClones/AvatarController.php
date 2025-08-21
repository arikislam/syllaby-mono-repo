<?php

namespace App\Http\Controllers\Api\v1\RealClones;

use Laravel\Pennant\Feature;
use Illuminate\Http\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use App\Syllaby\RealClones\Avatar;
use App\Http\Controllers\Controller;
use App\Syllaby\RealClones\RealClone;
use App\Http\Resources\AvatarResource;

class AvatarController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    /**
     * List of available avatars.
     */
    public function index(): JsonResponse
    {
        if (Feature::inactive('video')) {
            return $this->errorUnsupportedFeature();
        }

        $avatars = Avatar::active()->ownedBy($this->user())->latest('updated_at')->get();

        return $this->respondWithPagination(
            AvatarResource::collection($avatars)->additional([
                'popular' => $this->metrics(),
            ])
        );
    }

    /**
     * Deletes from storage a given avatar.
     */
    public function destroy(Avatar $avatar): JsonResponse|Response
    {
        if (Feature::inactive('video')) {
            return $this->errorUnsupportedFeature();
        }

        $this->authorize('delete', $avatar);

        $avatar->delete();

        return response()->noContent();
    }

    private function metrics(): Collection
    {
        return RealClone::whereRelation('avatar', 'type', '=', Avatar::STANDARD)
            ->select(['avatar_id', DB::raw('count(*) as used')])
            ->whereNotIn('avatar_id', ['Angela-inblackskirt-20220820', 'amy-Aq6OmGZnMt'])
            ->groupBy('avatar_id')
            ->orderBy('used', 'desc')
            ->having('used', '>', 10)
            ->limit(5)
            ->pluck('used', 'avatar_id');
    }
}
