<?php

namespace App\Http\Controllers\Api\v1\User;

use App\Http\Controllers\Controller;
use App\Http\Resources\CreditHistoryResource;
use App\Syllaby\Credits\Services\CreditService;

class CreditHistoryController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    public function index(CreditService $credits)
    {
        $order = request()->query('order', 'DESC');

        if (!in_array($order, ['ASC', 'DESC'])) {
            $order = 'DESC';
        }

        $transactions = $credits->historyFor($this->user(), $this->take(), $order);

        return $this->respondWithPagination(CreditHistoryResource::collection($transactions));
    }
}
