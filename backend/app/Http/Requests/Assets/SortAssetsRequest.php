<?php

namespace App\Http\Requests\Assets;

use App\Syllaby\Assets\Asset;
use Illuminate\Validation\Rule;
use App\Syllaby\Assets\VideoAsset;
use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Foundation\Http\FormRequest;

class SortAssetsRequest extends FormRequest
{
    public VideoAsset $asset;

    public ?VideoAsset $reference = null;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(Gate $gate): bool
    {
        return $gate->allows('sort', [Asset::class, $this->asset, $this->reference]);
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'model_id' => ['required', 'integer'],
            'model_type' => ['required', 'string', Rule::in('faceless')],
            'after_id' => ['nullable', 'integer', Rule::exists('assets', 'id')],
            'asset_id' => ['required', 'integer', 'different:after_id', Rule::exists('assets', 'id')],
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     */
    public function messages(): array
    {
        return [
            'asset_id.different' => 'Impossible to move the image after itself.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $this->asset = VideoAsset::where('asset_id', $this->input('asset_id'))
            ->where('model_id', $this->input('model_id'))
            ->where('model_type', $this->input('model_type'))
            ->first();

        $this->reference = VideoAsset::where('asset_id', $this->input('after_id'))
            ->where('model_id', $this->input('model_id'))
            ->where('model_type', $this->input('model_type'))
            ->first();
    }
}
