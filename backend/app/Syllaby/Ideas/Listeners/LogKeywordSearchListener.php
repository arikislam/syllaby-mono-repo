<?php

namespace App\Syllaby\Ideas\Listeners;

use App\Syllaby\Users\User;
use App\Syllaby\Ideas\Keyword;
use App\Syllaby\Ideas\KeywordUser;
use App\Syllaby\Ideas\Events\KeywordSearched;

class LogKeywordSearchListener
{
    public function handle(KeywordSearched $event): void
    {
        $user = $event->user;
        $keyword = $event->keyword;

        if (! blank($user) || ! blank($keyword)) {
            KeywordUser::create([
                'user_id' => $user->id,
                'keyword_id' => $keyword->id,
                'audience' => $event->audience,
                'created_at' => now()->toDateTimeString(),
                'updated_at' => now()->toDateTimeString(),
            ]);
        }
    }

    public function keyword(User $user, Keyword $keyword): ?KeywordUser
    {
        return KeywordUser::where('user_id', $user->id)
            ->where('keyword_id', $keyword->id)
            ->first();
    }
}
