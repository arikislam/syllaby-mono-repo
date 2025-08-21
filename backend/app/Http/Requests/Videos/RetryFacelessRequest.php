<?php

namespace App\Http\Requests\Videos;

use App\Syllaby\Videos\Video;
use App\Syllaby\Videos\Faceless;
use Illuminate\Auth\Access\Response;
use App\Syllaby\Generators\Generator;
use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Foundation\Http\FormRequest;

class RetryFacelessRequest extends FormRequest
{
    public function authorize(Gate $gate): Response
    {
        /** @var Faceless $faceless */
        $faceless = $this->route('faceless');

        $credits = $gate->inspect('faceless', [Generator::class, $faceless, $faceless->voice_id]);

        return match (true) {
            $credits->denied() => $credits,
            default => $gate->inspect('render', [$faceless->video, Video::FACELESS]),
        };
    }
}
