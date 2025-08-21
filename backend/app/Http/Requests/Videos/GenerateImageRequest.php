<?php

namespace App\Http\Requests\Videos;

use App\Syllaby\Videos\Faceless;
use App\Syllaby\Trackers\Tracker;
use Illuminate\Auth\Access\Response;
use App\Syllaby\Generators\Generator;
use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Foundation\Http\FormRequest;
use App\Syllaby\Credits\Enums\CreditEventEnum;

class GenerateImageRequest extends FormRequest
{
    public Tracker $tracker;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(Gate $gate): Response
    {
        /** @var Faceless $faceless */
        $faceless = $this->route('faceless');

        if ($gate->denies('update', $faceless)) {
            return Response::deny();
        }

        $this->tracker = $this->fetchTracker($faceless);

        if ($this->tracker->limit > $this->tracker->count) {
            return Response::allow();
        }

        $action = CreditEventEnum::SINGLE_AI_IMAGE_GENERATED;

        return $gate->inspect('generate', [Generator::class, $action]);
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'index' => ['required', 'integer', 'min:0'],
            'description' => ['nullable', 'string', 'max:500'],
        ];
    }

    /**
     * Fetch the image generation tracker for the given faceless video.
     */
    private function fetchTracker(Faceless $faceless): Tracker
    {
        return $faceless->trackers()->lockForUpdate()
            ->where('name', 'image-generation')
            ->first();
    }
}
