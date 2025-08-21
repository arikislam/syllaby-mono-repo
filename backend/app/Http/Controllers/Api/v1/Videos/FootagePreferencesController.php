<?php

namespace App\Http\Controllers\Api\v1\Videos;

use Arr;
use App\Syllaby\Videos\Footage;
use App\Http\Controllers\Controller;
use App\Http\Resources\FootageResource;
use App\Http\Requests\Videos\UpdateFootagePreferenceRequest;

class FootagePreferencesController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct()
    {
        $this->middleware(['auth:sanctum', 'subscribed']);
    }

    public function update(UpdateFootagePreferenceRequest $request, Footage $footage)
    {
        $footage->update(Arr::arrow($request->validated()));

        return $this->respondWithResource(FootageResource::make($footage));
    }
}
