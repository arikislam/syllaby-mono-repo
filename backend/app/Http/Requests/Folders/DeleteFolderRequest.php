<?php

namespace App\Http\Requests\Folders;

use Illuminate\Foundation\Http\FormRequest;
use App\Syllaby\Folders\Rules\ValidResource;

class DeleteFolderRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'resources' => ['required', 'array', 'min:1', new ValidResource],
            'resources.*' => ['required', 'integer'],
            'delete_unused_assets' => ['nullable', 'boolean'],
        ];
    }
}
