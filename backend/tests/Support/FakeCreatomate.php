<?php

namespace Tests\Support;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use PHPUnit\Framework\Assert as PHPUnit;
use Illuminate\Support\Testing\Fakes\Fake;

class FakeCreatomate implements Fake
{
    /**
     * Returns the current driver instance.
     */
    public function driver(): static
    {
        return $this;
    }

    /**
     * Asserts the source was successfully replaced.
     */
    public function assertRealCloneReplaced(array $source, string $url): void
    {
        $elements = Arr::map($source['elements'], function ($element) use ($url) {
            if ($this->replaceable($element)) {
                Arr::set($element, 'source', $url);
                Arr::set($element, 'type', 'video');
            }

            return $element;
        });

        $replaced = array_merge($source, compact('elements'));

        PHPUnit::assertTrue(
            collect($replaced['elements'])->some(fn ($element) => Arr::has($element, 'source') && $element['source'] === $url),
            'The expected digital twin source was not replaced.'
        );
    }

    /**
     * Check if the given element is the replaceable avatar.
     */
    private function replaceable(array $element): bool
    {
        $name = Arr::get($element, 'name');

        return $name && Str::startsWith($name, 'avatar-');
    }
}
