<?php

namespace App\Syllaby\Scraper;

use App\Syllaby\Users\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\MassPrunable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScraperLog extends Model
{
    use MassPrunable;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'response' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function prunable()
    {
        return $this->query()->where('created_at', '<', now()->subMonths(3));
    }
}
