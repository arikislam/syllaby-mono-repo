<?php

namespace App\Syllaby\Schedulers\Casts;

use InvalidArgumentException;
use Illuminate\Database\Eloquent\Model;
use App\Syllaby\Schedulers\DTOs\Options;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Contracts\Database\Eloquent\SerializesCastableAttributes;

class OptionsCast implements CastsAttributes, SerializesCastableAttributes
{
    /**
     * Get the options.
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): ?Options
    {
        if (blank($value)) {
            return null;
        }

        return Options::fromModel(json_decode($value, true));
    }

    /**
     * Set the options.
     */
    public function set(Model $model, string $key, mixed $options, array $attributes): ?string
    {
        if (blank($options)) {
            return null;
        }

        if (! $options instanceof Options) {
            throw new InvalidArgumentException('The given value is not an Options instance.');
        }

        return json_encode($options->toArray());
    }

    /**
     * Serialize the options.
     */
    public function serialize(Model $model, string $key, mixed $value, array $attributes): Options
    {
        $value = json_encode($value);

        return $this->get($model, $key, $value, $attributes);
    }
}
