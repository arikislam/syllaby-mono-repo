<?php

namespace App\Http\Requests\Videos;

use App\Syllaby\Videos\Video;
use Illuminate\Auth\Access\Response;
use App\Syllaby\Generators\Generator;
use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Foundation\Http\FormRequest;
use App\Syllaby\Credits\Enums\CreditEventEnum;

class RenderFootageRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(Gate $gate): Response
    {
        $footage = $this->route('footage');

        $action = CreditEventEnum::VIDEO_GENERATED;
        $credits = $gate->inspect('generate', [Generator::class, $action]);

        return match (true) {
            $credits->denied() => $credits,
            default => $gate->inspect('render', [$footage->video, Video::CUSTOM]),
        };
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'duration' => ['required', 'integer'],
        ];
    }
}
