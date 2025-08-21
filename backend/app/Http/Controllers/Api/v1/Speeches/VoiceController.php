<?php

namespace App\Http\Controllers\Api\v1\Speeches;

use App\Syllaby\Speeches\Voice;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Http\Resources\VoiceResource;
use App\Syllaby\RealClones\RealClone;

class VoiceController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    /**
     * List all available voices.
     */
    public function index(): JsonResponse
    {
        $voices = Voice::active()->ownedBy($this->user())
            ->orderBy('order')
            ->get();

        return $this->respondWithPagination(
            VoiceResource::collection($voices)->additional([
                'popular' => $this->metrics(),
            ])
        );
    }

    public function metrics(): Collection
    {
        return RealClone::whereRelation('voice', 'type', '=', Voice::STANDARD)
            ->select(['voice_id', DB::raw('count(*) as used')])
            ->groupBy('voice_id')
            ->orderBy('used', 'desc')
            ->having('used', '>', 10)
            ->limit(5)
            ->pluck('used', 'voice_id');
    }
}
