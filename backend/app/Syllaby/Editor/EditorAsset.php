<?php

namespace App\Syllaby\Editor;

use App\Syllaby\Users\User;
use App\System\Traits\HasActiveFlag;
use App\System\Contracts\Activatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Database\Factories\EditorAssetFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class EditorAsset extends Model implements Activatable
{
    use HasActiveFlag, HasFactory;

    const string TEXT_PRESET = 'text-preset';

    const string FONT = 'font';

    protected $guarded = [];

    protected $casts = [
        'preview' => 'array',
        'value' => 'array',
        'active' => 'boolean',
    ];

    public function scopeOwnedBy(Builder $query, User $user): Builder
    {
        return $query->whereNull('user_id')->orWhere('user_id', $user->id);
    }

    protected static function newFactory(): EditorAssetFactory
    {
        return EditorAssetFactory::new();
    }
}
