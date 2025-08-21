<?php

namespace App\Syllaby\Loggers;

use Illuminate\Database\Eloquent\Model;

class Suppression extends Model
{
    /**
     * The attributes that aren't mass assignable.
     */
    protected $guarded = [];

    /**
     * The attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'bounced_at' => 'datetime',
            'complained_at' => 'datetime',
        ];
    }
}
