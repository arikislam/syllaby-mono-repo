<?php

namespace App\Syllaby\Folders\Rules;

use Closure;
use App\Syllaby\Folders\Resource;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Contracts\Validation\ValidationRule;

class ValidResource implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $resources = Resource::query()
            ->whereIn('id', $value)
            ->where('user_id', auth()->id())
            ->get();

        if ($resources->isEmpty()) {
            $fail(__('folders.invalid-resource'));
        }

        if ($this->isRoot($resources)) {
            $fail(__('folders.delete-default-folder'));
        }

        if ($this->hasChildren($resources)) {
            $fail(__('folders.delete-folder-with-children'));
        }
    }

    private function isRoot(Collection $resources): bool
    {
        return $resources->contains(fn (Resource $resource) => $resource->isRoot());
    }

    private function hasChildren(Collection $resources): bool
    {
        return $resources->contains(fn (Resource $resource) => $resource->children()->exists());
    }
}
