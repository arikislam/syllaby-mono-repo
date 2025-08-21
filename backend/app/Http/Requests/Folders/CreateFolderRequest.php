<?php

namespace App\Http\Requests\Folders;

use App\Syllaby\Folders\Folder;
use App\Syllaby\Folders\Resource;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Database\Eloquent\Relations\Relation;

class CreateFolderRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'parent_id' => ['required', 'integer', 'exists:resources,id'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if (blank($this->input('parent_id'))) {
            $this->merge(['parent_id' => $this->rootFolder()->id]);
        }
    }

    protected function rootFolder(): Resource
    {
        return Resource::where('user_id', auth()->id())
            ->where('model_type', Relation::getMorphAlias(Folder::class))
            ->whereNull('parent_id')
            ->sole();
    }
}
