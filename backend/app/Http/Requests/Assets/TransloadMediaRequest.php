<?php

namespace App\Http\Requests\Assets;

use Illuminate\Support\Arr;
use App\Syllaby\Assets\Media;
use Illuminate\Support\Facades\Http;
use Illuminate\Auth\Access\Response;
use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Foundation\Http\FormRequest;
use App\Syllaby\Assets\Rules\ValidRemoteMedia;

class TransloadMediaRequest extends FormRequest
{
    /**
     * Url headers content.
     */
    public readonly array $details;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(Gate $gate): Response
    {
        $size = Arr::get($this->details, 'size', PHP_INT_MAX);

        return $gate->inspect('upload', [Media::class, $size]);
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'type' => ['required', 'string'],
            'url' => ['required', 'url', 'active_url', new ValidRemoteMedia($this->details)],
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $response = Http::head($this->input('url'));

        $this->details = [
            'mime-type' => $this->input('type'),
            'size' => $response->header('Content-Length'),
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     */
    public function messages(): array
    {
        return [
            'url.active_url' => 'This url is either broken or unreachable',
        ];
    }
}
