<?php

namespace App\Syllaby\Ideas\Actions;

use App\Syllaby\Ideas\Idea;
use App\Syllaby\Users\Enums\UserType;
use Illuminate\Pagination\LengthAwarePaginator;

class IdeasSuggestionsAction
{
    /**
     * Gets a paginated list of ideas suggestions for a specific audience.
     */
    public function handle(string $audience, int $count): LengthAwarePaginator
    {
        $query = Idea::query()
            ->select('ideas.*')
            ->join('keyword_user', 'keyword_user.keyword_id', '=', 'ideas.keyword_id')
            ->where('keyword_user.audience', $audience)
            ->where('ideas.public', true)
            ->where('keyword_user.user_id', function ($query) {
                $query->select('id')->from('users')->where('user_type', UserType::ADMIN->value)->limit(1);
            })
            ->orderByRaw('RAND()');

        return $query->paginate($count)->withQueryString();
    }
}
