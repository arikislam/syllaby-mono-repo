<?php

namespace App\Http\Requests\Animation;

use App\Syllaby\Assets\Asset;
use Illuminate\Validation\Validator;
use App\Syllaby\Assets\Enums\AssetType;
use App\System\Traits\HandlesThrottling;
use Illuminate\Foundation\Http\FormRequest;

class CreateAnimationRequest extends FormRequest
{
    use HandlesThrottling;

    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('faceless'));
    }

    public function rules(): array
    {
        return [
            'animations' => ['required', 'array', 'min:1', 'max:500'],
            'animations.*.id' => ['required', 'integer'],
            'animations.*.index' => ['required', 'integer'],
            'animations.*.prompt' => ['sometimes', 'string', 'max:500'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                if ($this->duplicateIndices()) {
                    $validator->errors()->add('animations', 'Animation indices must be unique.');
                }

                if (! $this->validAssets()) {
                    $validator->errors()->add('animations', 'One or more assets are invalid or do not exist.');
                }
            },
        ];
    }

    public function messages(): array
    {
        return [
            'animations.required' => 'At least one animation is required.',
            'animations.max' => 'Maximum 500 animations allowed per request.',
            'animations.*.id.required' => 'Asset ID is required for each animation.',
            'animations.*.id.exists' => 'One or more assets do not exist.',
            'animations.*.index.required' => 'Index is required for each animation.',
            'animations.*.prompt.max' => 'Animation prompt cannot exceed 500 characters.',
        ];
    }

    private function validAssets(): bool
    {
        $animations = $this->collect('animations');

        $assets = Asset::where('user_id', $this->user()->id)
            ->whereIn('id', $animations->pluck('id'))
            ->whereIn('type', [AssetType::CUSTOM_IMAGE, AssetType::AI_IMAGE, AssetType::STOCK_IMAGE])
            ->get();

        return $assets->count() === $animations->count();
    }

    private function duplicateIndices(): bool
    {
        $indices = $this->collect('animations')->pluck('index');

        return $indices->count() !== $indices->unique()->count();
    }
}
