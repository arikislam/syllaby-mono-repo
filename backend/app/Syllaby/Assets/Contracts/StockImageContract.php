<?php

namespace App\Syllaby\Assets\Contracts;

use App\Syllaby\Assets\Vendor\StockMedia\DTOs\ImageData;

interface StockImageContract
{
    /**
     * Display the image with the given id.
     */
    public function find(string|int $id): ?ImageData;

    /**
     * Search images for the given terms.
     */
    public function search(array $data): ?array;

    /**
     * Retrieves user collection from stock media provider.
     */
    public function collection(string $id, array $data): ?array;
}
