<?php

namespace App\System\Traits;

use Illuminate\Database\Eloquent\Builder;

/**
 * @property bool $active
 *
 * @method static Builder active()
 * @method static Builder inactive()
 */
trait HasActiveFlag
{
    public function isActive(): bool
    {
        if ($this->active === null) {
            $this->refresh();
        }

        return $this->active;
    }

    public function setActive(): bool
    {
        $this->active = true;

        return $this->save();
    }

    public function setInactive(): bool
    {
        $this->active = false;

        return $this->save();
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('active', true);
    }

    public function scopeInactive(Builder $query): Builder
    {
        return $query->where('active', false);
    }
}