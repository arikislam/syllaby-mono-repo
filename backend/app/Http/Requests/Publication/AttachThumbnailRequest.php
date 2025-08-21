<?php

namespace App\Http\Requests\Publication;

use Illuminate\Validation\Rule;
use Illuminate\Database\Query\Builder;
use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Foundation\Http\FormRequest;
use App\Syllaby\Publisher\Publications\Publication;
use App\Syllaby\Publisher\Channels\Enums\SocialAccountEnum;

class AttachThumbnailRequest extends FormRequest
{
    public Publication $publication;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(Gate $gate): bool
    {

        return $gate->allows('update', $this->route('publication'));
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'provider' => ['required', 'string', Rule::in($this->providers())],
            'asset_id' => ['required', 'integer', Rule::exists('assets', 'id')->where(function (Builder $query) {
                $query->where('user_id', auth()->id());
            })],
        ];
    }

    /**
     * Get the list of providers supporting thumbnails.
     */
    protected function providers(): array
    {
        return [
            SocialAccountEnum::Youtube->toString(),
            SocialAccountEnum::LinkedIn->toString(),
            SocialAccountEnum::Facebook->toString(),
        ];
    }
}
