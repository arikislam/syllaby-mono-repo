<?php

namespace App\Syllaby\Subscriptions\Contracts;

interface GooglePlayApiClientInterface
{
    public function makeRequest(string $method, string $endpoint, array $data = [], array $context = []): array;

    public function getAccessToken(): string;
}
