<?php

namespace App\Http\Requests\Assets;

use App\Syllaby\Assets\Asset;
use Illuminate\Validation\Validator;
use App\Syllaby\Assets\Enums\AssetStatus;
use Illuminate\Foundation\Http\FormRequest;

class BulkDeleteAssetsRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'assets' => ['required', 'array', 'min:1', 'max:100'],
            'assets.*' => ['required', 'integer', 'distinct'],
        ];
    }

    public function messages(): array
    {
        return [
            'assets.min' => 'At least one asset must be provided.',
            'assets.max' => 'You can delete a maximum of 100 assets at once.',
            'assets.*.distinct' => 'Duplicate assets are not allowed.',
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                if ($this->activelyUsedAssets()) {
                    $validator->errors()->add('assets', 'Please make sure that assets are not actively used or being processed.');
                }
            },
        ];
    }

    private function activelyUsedAssets(): bool
    {
        $assets = Asset::query()
            ->whereIn('id', $this->input('assets', []))
            ->where('user_id', $this->user()->id)
            ->where('status', '<>', AssetStatus::PROCESSING)
            ->whereDoesntHave('videos')
            ->count();

        return $assets !== count($this->input('assets', []));
    }
}
