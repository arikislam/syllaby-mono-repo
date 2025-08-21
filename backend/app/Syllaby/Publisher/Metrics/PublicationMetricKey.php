<?php

namespace App\Syllaby\Publisher\Metrics;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Database\Factories\PublicationMetricKeyFactory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PublicationMetricKey extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
    ];

    public function values(): HasMany
    {
        return $this->hasMany(PublicationMetricValue::class, 'publication_metric_key_id');
    }

    protected static function newFactory(): Factory
    {
        return PublicationMetricKeyFactory::new();
    }
}
