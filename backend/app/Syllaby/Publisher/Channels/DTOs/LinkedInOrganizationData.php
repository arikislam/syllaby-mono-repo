<?php

namespace App\Syllaby\Publisher\Channels\DTOs;

use Arr;

readonly class LinkedInOrganizationData
{
    public function __construct(
        public string $provider_id,
        public string $organization_id,
        public string $organization_name,
        public string $role,
    ) {
    }

    public static function fromResponse(array $response, array $metadata): array
    {
        return collect($response)->map(fn(array $element) => new self(
            provider_id: Arr::get($metadata, 'provider_id'),
            organization_id: Arr::get($element, 'organization~.id'),
            organization_name: Arr::get($element, 'organization~.localizedName'),
            role: Arr::get($element, 'role')
        ))->toArray();
    }
}