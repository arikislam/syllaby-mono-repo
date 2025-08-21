<?php

namespace App\Syllaby\Ideas;

use Illuminate\Database\Eloquent\Relations\Pivot;

class KeywordUser extends Pivot
{
    protected $table = 'keyword_user';

    /**
     * The attributes that aren't mass assignable.
     */
    protected $guarded = [];

}
