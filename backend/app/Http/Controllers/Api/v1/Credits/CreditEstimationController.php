<?php

namespace App\Http\Controllers\Api\v1\Credits;

use App\Http\Controllers\Controller;
use App\Http\Requests\Credit\EstimateCreditRequest;
use App\Syllaby\Credits\Services\CreditEstimationService;

class CreditEstimationController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth:sanctum', 'subscribed']);
    }

    public function store(EstimateCreditRequest $request)
    {
        $credits = match ($request->type) {
            'idea' => CreditEstimationService::idea(),
            'script' => CreditEstimationService::script(),
            'speech' => CreditEstimationService::speech(),
            'video' => CreditEstimationService::video($request->input('provider')),
            'faceless' => CreditEstimationService::faceless(
                $request->input('provider'),
                $request->input('script'),
                $request->boolean('has_genre')
            ),
            'real_clone' => [
                'speech' => CreditEstimationService::speech(),
                'video' => CreditEstimationService::video($request->input('provider')),
            ],
        };

        return $this->respondWithArray(['credits' => $credits]);
    }
}
