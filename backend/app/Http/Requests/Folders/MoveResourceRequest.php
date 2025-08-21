<?php

namespace App\Http\Requests\Folders;

use Illuminate\Validation\Rule;
use App\Syllaby\Folders\Resource;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;

class MoveResourceRequest extends FormRequest
{
    public function authorize(): bool
    {
        if ($this->user()->cannot('move', $this->route('destination'))) {
            throw ValidationException::withMessages(['destination' => __('folders.move-resource')]);
        }

        if (! $this->isDestinationFolder()) {
            throw ValidationException::withMessages(['destination' => __('folders.move-non-folder')]);
        }

        return true;
    }

    public function rules(): array
    {
        return [
            'resources' => ['required', 'array'],
            'resources.*' => [
                'required',
                'integer',
                Rule::exists('resources', 'id')->where('user_id', $this->user()->id)->whereNotNull('parent_id'),
                function ($attribute, $value, $fail) {
                    if ($value === $this->route('destination')->id) {
                        $fail(__('folders.cannot-move-to-self'));
                    }
                },
            ],
        ];
    }

    private function isDestinationFolder(): bool
    {
        /** @var resource $destination */
        $destination = $this->route('destination');

        return $destination->isFolder();
    }
}
