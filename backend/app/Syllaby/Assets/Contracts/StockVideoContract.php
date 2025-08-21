<?php

namespace App\Syllaby\Assets\Contracts;

use App\Syllaby\Assets\Vendor\StockMedia\DTOs\VideoData;

interface StockVideoContract
{
    /**
     * Display the video with the given id.
     */
    public function find(string|int $id): ?VideoData;

    /**
     * Search videos for the given terms.
     */
    public function search(array $data): ?array;

    /**
     * Retrieves user collection from stock media provider.
     */
    public function collection(string $id, array $data): ?array;
}
