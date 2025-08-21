<?php

namespace App\Http\Controllers\Api\v1\Videos;

use App\Syllaby\Videos\Faceless;
use App\Http\Controllers\Controller;
use App\Http\Resources\VideoResource;
use App\Syllaby\Videos\Enums\VideoStatus;
use App\Syllaby\Videos\Actions\ConvertFacelessAction;

class ConvertFacelessController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth:sanctum', 'subscribed']);
    }

    public function store(Faceless $faceless, ConvertFacelessAction $action)
    {
        $this->authorize('convert', $faceless);

        if ($faceless->video->status !== VideoStatus::COMPLETED) {
            return $this->errorWrongArgs('The video has not been rendered yet');
        }

        $video = attempt(fn () => $action->handle($faceless, $this->user()));

        return $this->respondWithResource(new VideoResource($video));
    }
}
