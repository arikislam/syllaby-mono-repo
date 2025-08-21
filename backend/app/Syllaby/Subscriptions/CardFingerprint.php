<?php

namespace App\Syllaby\Subscriptions;

use Illuminate\Database\Eloquent\Model;
use Database\Factories\CardFingerprintFactory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CardFingerprint extends Model
{
    use HasFactory;

    /**
     * The attributes that aren't mass assignable.
     */
    protected $guarded = [];

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): Factory
    {
        return CardFingerprintFactory::new();
    }
}
