<?php

namespace App\Http\Requests\Publication;

use Illuminate\Auth\Access\Response;
use Illuminate\Validation\Rule;
use Illuminate\Contracts\Auth\Access\Gate;
use App\Http\Requests\Assets\UploadMediaRequest;
use App\Syllaby\Publisher\Channels\Enums\SocialAccountEnum;

/**
 * @property-read string $path
 * @property-read string $provider
 */
class CreateThumbnailRequest extends UploadMediaRequest
{
    public function authorize(Gate $gate): Response
    {
        $response = parent::authorize($gate);

        if ($response->denied()) {
            return Response::deny($response->message())->withStatus($response->status());
        }

        return $gate->inspect('update', $this->route('publication'));
    }

    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'provider' => [
                'required', 'string',
                Rule::in([SocialAccountEnum::Youtube->toString(), SocialAccountEnum::LinkedIn->toString(), SocialAccountEnum::Facebook->toString()])
            ]
        ]);
    }
}
