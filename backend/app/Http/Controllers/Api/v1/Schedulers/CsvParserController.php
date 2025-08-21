<?php

namespace App\Http\Controllers\Api\v1\Schedulers;

use App\Http\Controllers\Controller;
use App\Http\Requests\Schedulers\CsvParserRequest;
use App\Syllaby\Schedulers\Actions\ParseCsvAction;

class CsvParserController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct()
    {
        $this->middleware(['auth:sanctum', 'subscribed']);
    }

    /**
     * Parse the CSV file.
     */
    public function store(CsvParserRequest $request, ParseCsvAction $parser)
    {
        $file = $request->file('file');

        return $this->respondWithArray($parser->handle($file));
    }
}
