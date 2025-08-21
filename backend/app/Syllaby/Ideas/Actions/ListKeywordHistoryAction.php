<?php

namespace App\Syllaby\Ideas\Actions;

use App\Syllaby\Users\User;
use App\Syllaby\Ideas\Keyword;
use Illuminate\Support\Facades\DB;
use Spatie\QueryBuilder\QueryBuilder;
use Spatie\QueryBuilder\AllowedFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ListKeywordHistoryAction
{
    /**
     * Base Keyword fields to be displayed.
     */
    private array $attributes = [
        'keywords.id',
        'keywords.name',
        'keywords.slug',
        'keywords.network',
        'keywords.created_at',
        'keywords.updated_at',
    ];

    /**
     * Queries the database to display given user most recent keyword searches.
     */
    public function handle(User $user, int $count, bool $metrics): LengthAwarePaginator
    {
        $query = $this->buildBaseQuery($user);

        if ($metrics) {
            $query = $this->withMetrics($query);
        }

        return QueryBuilder::for($query)->allowedFilters([
            AllowedFilter::exact('network'),
        ])->paginate($count)->withQueryString();
    }

    /**
     * Builds the base query depending on if metrics needs to be shown.
     */
    private function buildBaseQuery(User $user): Builder
    {
        return Keyword::query()->join('keyword_user', static function (JoinClause $join) use ($user) {
            $join->on('keywords.id', '=', 'keyword_user.keyword_id')
                ->where('keyword_user.user_id', '=', $user->id);
        })->orderBy('keyword_user.updated_at', 'desc');
    }

    /**
     * Adds the metrics fields to the query.
     */
    private function withMetrics(Builder $query): Builder
    {
        return $query->select($this->attributes())
            ->leftJoin('ideas', 'keywords.id', 'ideas.keyword_id')
            ->leftJoin(DB::raw('(SELECT ideas.id, keyword_id, ROW_NUMBER() OVER (PARTITION BY keyword_id ORDER BY ideas.id ASC) as rn_asc, ROW_NUMBER() OVER (PARTITION BY keyword_id ORDER BY ideas.id DESC) as rn_desc FROM ideas) as ranked_ideas'), function ($join) {
                $join->on('ideas.id', 'ranked_ideas.id')->on('keywords.id', 'ranked_ideas.keyword_id');
            })->groupBy($this->attributes, 'keyword_user.updated_at');
    }

    /**
     * List of custom attributes when metrics are required.
     */
    private function attributes(): array
    {
        return [
            ...$this->attributes,
            DB::raw('COALESCE(AVG(ideas.trend), 0) as trend_avg'),
            DB::raw('COALESCE(MAX(CASE WHEN rn_asc = 1 THEN ideas.cpc END), 0) as cpc_min'),
            DB::raw('COALESCE(MAX(CASE WHEN rn_desc = 1 THEN ideas.cpc END), 0) as cpc_max'),
            DB::raw('COALESCE(MAX(CASE WHEN rn_asc = 1 THEN ideas.volume END), 0) as volume_min'),
            DB::raw('COALESCE(MAX(CASE WHEN rn_desc = 1 THEN ideas.volume END), 0) as volume_max'),
            DB::raw('COALESCE(MAX(CASE WHEN rn_asc = 1 THEN ideas.competition END), 0) as competition_min'),
            DB::raw('COALESCE(MAX(CASE WHEN rn_desc = 1 THEN ideas.competition END), 0) as competition_max'),
        ];
    }
}
