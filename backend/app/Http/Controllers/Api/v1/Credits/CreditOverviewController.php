<?php

namespace App\Http\Controllers\Api\v1\Credits;

use App\Http\Controllers\Controller;

class CreditOverviewController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth:sanctum', 'subscribed']);
    }

    public function index()
    {
        return $this->respondWithArray([
            'idea_discovery' => ['credits' => 15],
            'avatar' => ['credits' => 30],
            'faceless' => ['credits' => 13],
            'image_regenerations' => ['credits' => 1, 'free' => 3],
            'thumbnail_generation' => ['credits' => 1],
            'ai_scripts' => ['credits' => 1],
            'export_videos' => ['credits' => 6],
        ]);
    }
}
